<?php

namespace App\Http\Controllers;
set_time_limit(0);

use Illuminate\Http\Request;
use App\Jobs\ConvertVideoResolution;
use App\Jobs\CreateThumbnails;
use App\Jobs\CreateThumbnailImages;
use App\Models\Video;
use App\Models\ContentCategory;
use App\Models\ContentTag;
use App\Models\Channel;
use App\Models\SiteModel as Model;
use App\Models\ProgressImport;
use App\Models\ImportedFile;
use App\Models\Grabber;
use App\Models\GrabberItem;
use YouTube\YouTubeDownloader;
use YouTube\Exception\YouTubeException;
use Pawlox\VideoThumbnail\Facade\VideoThumbnail;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\GrabberItemController;
use Cregennan\PornhubToolkit\Toolkit as PHToolkit;
use GuzzleHttp\Client;
use Symfony\Component\Panther\Client as ChromeClient;
use Symfony\Component\DomCrawler\Crawler;
use Facebook\WebDriver\Remote\DesiredCapabilities;


class ImportController extends Controller
{
    public function parseData($content, $delimStart, $delimEnd, $position = 0){
        $data = explode($delimStart, $content);
        if(isset($data[$position + 1])){
            $data = $data[$position + 1];
            $data = explode($delimEnd, $data)[0];
            return $data;
        }
        else {
            return null;
        }
    }

    public function youtube(Request $request) {
        $urls = preg_split("/\r\n|\r|\n/", $request->source);
        $grabber = Grabber::find($request->id);
        $grabber->update(["queue" => (int)$grabber->queue + count($urls)]);
        $progress = new ProgressImport;
        $progress->save();
        $i = 0;
        foreach($urls as $url){
            $i++;

            //Add to history log
            $grabberItem = new GrabberItem([
                "grabber_id" => $grabber->id,
                "url" => $url,
                "message" => "",
                "status" => 1
            ]);
            $grabberItem->save();

            try {
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                $source = curl_exec($curl);
                $title = $this->parseData($source, "<title>", " - YouTube");
                $description = $this->parseData($source, ',"shortDescription":"', '","isCrawlable"');
                $views = $this->parseData($source, 'interactionCount" content="', '"');
                $command = "chromium-browser --headless --disable-gpu --dump-dom $url";
                exec($command, $output, $returnVar);
                $output = implode(" ", $output);
                $likes = $this->parseData($output, 'like this video along with ', ' other people');
                $likes = filter_var($likes, FILTER_SANITIZE_NUMBER_INT);
                $code = $this->parseData($url, 'v=', '&');
                $embed = "https://www.youtube.com/embed/$code";
                $duration = $this->parseData($source, '"approxDurationMs":"', '"');
                $duration = (int)$duration / 1000;
                if($grabber->title_limit > 0){
                    $title = substr($title, 0, $grabber->title_limit);
                }
                if($grabber->description_limit > 0){
                    $description = substr($description, 0, $grabber->description_limit);
                }
                if(!Video::where('source', 'LIKE', '%' . $code . '%')->exists() || $grabber->duplicated == 2){
                    $video = new Video([
                        "name" => strpos($grabber->data, "1") !== false ? $title : "New Import",
                        "description" => strpos($grabber->data, "2") !== false ? $description : " ",
                        "file" => "Grabber",
                        "type" => $grabber->type,
                        "status" => 2,
                        "source" => "YouTube.com;$url",
                        "views" => strpos($grabber->data, "4") !== false ? (int)$views : 0,
                        "likes" => strpos($grabber->data, "3") !== false ? (int)$likes : 0,
                        "duration" => (int)$duration,
                        "embed" => (int)$grabber->importType === 1 ? $embed : "",
                        "title_slug" => UtilsController::createSlug($title, "videos", "title_slug"),
                        "date_scheduled" => date('Y-m-d H:i:s')
                    ]);
                    $video->save();  
                    $id = hash("crc32", $video->id);
                    $slug = str_slug($title, "-");
                    $importedFile = new ImportedFile([
                        "slug" => "$id-$slug",
                        "url" => $url
                    ]);
                    $importedFile->save();
                    $progress->files()->save($importedFile);
                    if((int)$grabber->importType === 2){
                        $downloadCommand = "yt-dlp --merge-output-format mp4 -o '".base_path("public/videos/$id-$slug.mp4")."' $url";
                        //return $downloadCommand;
                        exec($downloadCommand, $output, $returnCode);
                        //$this->resoultionConvert($id, $slug);
                        CreateThumbnailImages::dispatch("$id-$slug.mp4", "$id-$slug");
                        ConvertVideoResolution::dispatch($id, $slug);
                        $video->update([
                            "slug" => "$id-$slug", 
                            "file" => "$id-$slug.mp4",
                            "status" => $grabber->status
                        ]);
                        //$videoFunctions = new VideoController;
                        //$videoFunctions->createThumbnails("$id-$slug.mp4", "$id-$slug");
                        CreateThumbnails::dispatch("$id-$slug.mp4", "$id-$slug");
                    }
                    else {
                        ob_start();
                        $formatListCommand = "yt-dlp --list-formats \"$url\"";
                        exec($formatListCommand, $formatsOutput);
                        $formatCode = "";
                        foreach ($formatsOutput as $line) {
                            if (strpos($line, 'mp4') !== false && strpos($line, '240p') === false) {
                                $formatParts = explode(" ", $line);
                                $formatCode = $formatParts[0];
                                if (strpos($line, 'video') !== false) {
                                    break;
                                }
                            }
                        }
                        $formatsOutput = ob_get_clean();
                        if (!empty($formatCode)) {
                            $command = "yt-dlp --format $formatCode -o \"".base_path("public/videos/$id-$slug.mp4")."\" \"$url\"";
                            exec($command, $output, $exitCode);
                        } else {
                            return "No suitable video format found.";
                        }
                        $video->update([
                            "slug" => "$id-$slug",
                            "file" => "$id-$slug.mp4",
                            "status" => $grabber->status
                        ]);
                        //$videoFunctions = new VideoController;
                        //$videoFunctions->createThumbnails("$id-$slug.mp4", "$id-$slug");
                        CreateThumbnails::dispatch("$id-$slug.mp4", "$id-$slug", true);
                    }
                }
                $grabber->update(["new" => 0]);
                $grabber->decrement('queue', 1);
                
                //Update history log - successful
                $grabberItem->update([
                    "status" => 2
                ]);
            } catch(\Throwable $e){
                $grabber->update(["new" => 0]);
                $grabber->decrement('queue', 1);
                
                //Update history log - failed
                $grabberItem->update([
                    "status" => 3,
                    "message" => $e
                ]);
                
                continue;
            }
        }
        return "ok";
    }

    public function xvideos(Request $request) {
        $urls = preg_split("/\r\n|\r|\n/", $request->source);
        $grabber = Grabber::find($request->id);
        $grabber->update(["queue" => (int)$grabber->queue + count($urls)]);
        $progress = new ProgressImport;
        $progress->save();
        foreach($urls as $i => $url){
            try {
                //Add to history log
                $grabberItem = new GrabberItem([
                    "grabber_id" => $grabber->id,
                    "url" => $url,
                    "message" => "",
                    "status" => 1
                ]);
                $grabberItem->save();
    
                $code = $this->parseData($url, '/video', '/');
                $code = str_replace(".", "", $code);
                $embed = "https://www.xvideos.com/embedframe/$code";
                $source = file_get_contents($url);
                $title = $this->parseData($source, "<title>", "</title>");
                $title = str_replace(' - XVIDEOS.COM', '', $title);
                $description = "";
                $views = $this->parseData($source, '<span class="icon-f icf-eye"></span><strong class="mobile-hide">', '<');
                $views = (int)filter_var($views, FILTER_SANITIZE_NUMBER_INT);
                $likes = $this->parseData($source, '<span class="rating-good-nbr">', '<');
                if (strpos($likes, "k") !== false) {
                    $likes = (float)$likes * 1000;
                } else {
                    $likes = (int)$likes;
                }
                $duration = $this->parseData($source, 'g:duration" content="', '"');
                if($grabber->title_limit > 0){
                    $title = substr($title, 0, $grabber->title_limit);
                }
                if($grabber->description_limit > 0){
                    $description = substr($description, 0, $grabber->description_limit);
                }
                $categories = [];
                if(strpos($grabber->data, "8") !== false){
                    $categories = $this->parseData($source, '"categories":"', '"');
                    $categories = explode( ",", $categories );
                    foreach ($categories as &$category) {
                        $category = str_replace("_", " ", $category);
                        $category = urldecode($category);
                        $checkExisting = ContentCategory::where('name', 'LIKE', $category)->whereRaw('LOWER(name) = ?', [strtolower($category)])->first();
                        if(!$checkExisting){
                            $newCategory = ContentCategory::create([
                                "name" => $category,
                                "status" => $grabber->status,
                                "title_slug" => UtilsController::createSlug($category, "content_categories", "title_slug")
                            ]);
                            $category = $newCategory->id;
                        }
                        else {
                            $category = $checkExisting->id;
                        }
                    }
                }
                $tags = [];
                if(strpos($grabber->data, "9") !== false){
                    $tags = $this->parseData($source, '"video_tags":[', "]");
                    $tags = explode( ",", $tags );
                    foreach ($tags as &$tag) {
                        $tag = str_replace(["-", '"'], [" ", ""], $tag);
                        $tag = urldecode($tag);
                        if(!empty($tag)){
                            $checkExisting = ContentTag::where('name', 'LIKE', $tag)->whereRaw('LOWER(name) = ?', [strtolower($tag)])->first();
                            if(!$checkExisting){
                                $request = new Request([
                                    "name" => $tag
                                ]);
                                $newTag = ContentTagController::store($request);
                                $tag = $newTag->original["data"]->id;
                            }
                            else {
                                $tag = $checkExisting->id;
                            }
                        }
                    }
                }
                $models = [];
                if(strpos($grabber->data, "5") !== false){
                    $models = $this->parseData($source, '"video_models":', '}};');
                    $models = json_decode($models);
                    foreach ($models as &$model) {
                        $model = $model->name;
                        $model = str_replace("-", " ", $model);
                        $model = urldecode($model);
                        $checkExisting = Model::where('stage_name', 'LIKE', $model)->whereRaw('LOWER(stage_name) = ?', [strtolower($model)])->first();
                        if(!$checkExisting){
                            $newModel = Model::create([
                                "stage_name" => $model,
                                "title_slug" => UtilsController::createSlug($model, "models", "title_slug")
                            ]);
                            $model = $newModel->id;
                        }
                        else {
                            $model = $checkExisting->id;
                        }
                    }
                }
                $channel = null;
                if(strpos($grabber->data, "7") !== false){
                    $channel = trim($this->parseData($source, '<span class="icon-f icf-device-tv-v2"></span>', '</span>', 1));
                    if(!empty($channel)){
                        $checkExisting = Channel::where('title', 'LIKE', $channel)->whereRaw('LOWER(title) = ?', [strtolower($channel)])->first();
                        if(!$checkExisting){
                            $newChannel = Channel::create([
                                "title" => $channel,
                                "title_slug" => UtilsController::createSlug($channel, "channels", "title_slug"),
                                "status" => $grabber->status
                            ]);
                            $channel = $newChannel->id;
                        }
                        else {
                            $channel = $checkExisting->id;
                        }
                    }
                }
                if(!Video::where('source', 'LIKE', '%' . $code . '%')->exists() || $grabber->duplicated == 2){
                    $video = new Video([
                        "source" => "XVideos.com;$url",
                        "duration" => $duration,
                        "name" => strpos($grabber->data, "1") !== false ? $title : "New Import",
                        "description" => strpos($grabber->data, "2") !== false ? $description : " ",
                        "file" => "Grabber",
                        "type" => $grabber->type,
                        "status" => 2,
                        "views" => strpos($grabber->data, "4") !== false ? $views : 0,
                        "likes" => strpos($grabber->data, "3") !== false ? $likes : 0,
                        "embed" => (int)$grabber->importType === 1 ? $embed : "",
                        "title_slug" => UtilsController::createSlug($title, "videos", "title_slug"),
                        "date_scheduled" => date('Y-m-d H:i:s')
                    ]);
                    $video->save();  
                    if($categories){
                        $video->categories()->attach($categories);
                    }
                    if($tags){
                        $video->tags()->attach($tags);
                    }
                    if($models){
                        $video->models()->attach($models);
                    }
                    if($channel){
                        $video->channels()->attach($channel);
                    }
                    $id = hash("crc32", $video->id);
                    $slug = str_slug($title, "-");
                    $videoUrl = $this->parseData($source, '"contentUrl": "', '",');
                    $importedFile = new ImportedFile([
                        "slug" => "$id-$slug",
                        "url" => $videoUrl
                    ]);
                    $importedFile->save();
                    $progress->files()->save($importedFile);
                    if((int)$grabber->importType === 2){
                        $downloadCommand = "yt-dlp --format bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best -o '".base_path("public/videos/$id-$slug.mp4")."' $url";
                        exec($downloadCommand, $output, $returnCode);
                        //file_put_contents(public_path('videos')."/$id-$slug.mp4", fopen($videoUrl, 'r'));
                        //$this->resoultionConvert($id, $slug);
                        CreateThumbnailImages::dispatch("$id-$slug.mp4", "$id-$slug");
                        ConvertVideoResolution::dispatch($id, $slug);
                        $video->update([
                            "slug" => "$id-$slug", 
                            "file" => "$id-$slug.mp4",
                            "status" => $grabber->status
                        ]);
                        //$videoFunctions = new VideoController;
                        //$videoFunctions->createThumbnails("$id-$slug.mp4", "$id-$slug");
                        CreateThumbnails::dispatch("$id-$slug.mp4", "$id-$slug");
                    }
                    else {
                        $command = "yt-dlp -o \"".base_path("public/videos/$id-$slug.mp4")."\" \"$url\"";
                        exec($command, $output, $exitCode);
                        $video->update([
                            "slug" => "$id-$slug",
                            "file" => "$id-$slug.mp4",
                            "status" => $grabber->status
                        ]);
                        //$videoFunctions = new VideoController;
                        //$videoFunctions->createThumbnails("$id-$slug.mp4", "$id-$slug");
                        CreateThumbnails::dispatch("$id-$slug.mp4", "$id-$slug", true);
                    }
                }
                $grabber->update(["new" => 0]);
                $grabber->decrement('queue', 1);
                
                //Update history log - successful
                $grabberItem->update([
                    "status" => 2
                ]);
            } catch(\Throwable $e){
                $grabber->update(["new" => 0]);
                $grabber->decrement('queue', 1);
                
                //Update history log - failed
                $grabberItem->update([
                    "status" => 3,
                    "message" => $e
                ]);
                
                continue;
            }
        }
        return "ok";
    }

    public function pornhub(Request $request){
        $urls = preg_split("/\r\n|\r|\n/", $request->source);
        $grabber = Grabber::find($request->id);
        $grabber->update(["queue" => (int)$grabber->queue + count($urls)]);
        $progress = new ProgressImport;
        $progress->save();
        \Log::error("PornHub import URLs: $request->source");
        foreach($urls as $i => $url){
            try {
                //Add to history log
                $grabberItem = new GrabberItem([
                    "grabber_id" => $grabber->id,
                    "url" => $url,
                    "message" => "",
                    "status" => 1
                ]);
                $grabberItem->save();
    
                $viewkey = explode("viewkey=", $url);
                $embed = "https://www.pornhub.com/embed/$viewkey[1]";
                $source = file_get_contents("https://www.pornhub.com/embed/$viewkey[1]");
                $command = "chromium-browser --headless --disable-gpu --dump-dom $url";
                exec($command, $output, $returnVar);
                $output = implode(" ", $output);
                $title = $this->parseData($output, '<title>', '</title>');
                $title = str_replace([" | Pornhub", " - Pornhub.com"], "", $title);
                $title = $this->clearString($title);
                if(empty($title)){
                    $title = "No title";
                }
                $description = "";
                $duration = $this->parseData($source, '"video_duration":', '"');
                $duration = (int)$duration;
                $likes = (int)$this->parseData($output, '"votesUp" data-rating="', '"');
                if(empty($duration)){
                    $duration = 0;
                }
                if($grabber->title_limit > 0){
                    $title = substr($title, 0, $grabber->title_limit);
                }
                if($grabber->description_limit > 0){
                    $description = substr($description, 0, $grabber->description_limit);
                }
                $categories = [];
                if(strpos($grabber->data, "8") !== false){
                    $categories = $this->parseData($output, "context_category%5D=", "&channel");
                    $categories = explode( "%2C", $categories );
                    foreach ($categories as &$category) {
                        $category = str_replace("-", " ", $category);
                        $category = urldecode($category);
                        $checkExisting = ContentCategory::where('name', 'LIKE', $category)->whereRaw('LOWER(name) = ?', [strtolower($category)])->first();
                        if(!$checkExisting){
                            $newCategory = ContentCategory::create([
                                "name" => $category,
                                "status" => $grabber->status,
                                "title_slug" => UtilsController::createSlug($category, "content_categories", "title_slug")
                            ]);
                            $category = $newCategory->id;
                        }
                        else {
                            $category = $checkExisting->id;
                        }
                    }
                }
                $tags = [];
                if(strpos($grabber->data, "9") !== false){
                    $tags = $this->parseData($output, "context_tag%5D=", "&channel");
                    $tags = explode( "%2C", $tags );
                    foreach ($tags as &$tag) {
                        $tag = str_replace("-", " ", $tag);
                        $tag = urldecode($tag);
                        if(!empty($tag)){
                            $checkExisting = ContentTag::where('name', 'LIKE', $tag)->whereRaw('LOWER(name) = ?', [strtolower($tag)])->first();
                            if(!$checkExisting){
                                $request = new Request([
                                    "name" => $tag
                                ]);
                                $newTag = ContentTagController::store($request);
                                $tag = $newTag->original["data"]->id;
                            }
                            else {
                                $tag = $checkExisting->id;
                            }
                        }
                    }
                }
                $models = [];
                if(strpos($grabber->data, "5") !== false){
                    $modelsArea = explode("Pornstars&nbsp;", $output);
                    if(isset($modelsArea[1])){
                        $modelsArea = explode("responseWrapper", $modelsArea[1]);
                        $models = explode('/pornstar/', $modelsArea[0]);
                        $models = array_slice($models, 1);
                        foreach ($models as &$model) {
                            $model = explode('"', $model);
                            $model = str_replace("-", " ", $model[0]);
                            $model = urldecode($model);
                            $model = ucwords($model);
                            $checkExisting = Model::where('stage_name', 'LIKE', $model)->whereRaw('LOWER(stage_name) = ?', [strtolower($model)])->first();
                            if(!$checkExisting){
                                $newModel = Model::create([
                                    "stage_name" => $model,
                                    "title_slug" => UtilsController::createSlug($model, "models", "title_slug")
                                ]);
                                $model = $newModel->id;
                            }
                            else {
                                $model = $checkExisting->id;
                            }
                        }
                    }
                }
                $channel = null;
                if(strpos($grabber->data, "7") !== false){
                    $channel = trim($this->parseData($output, '"author" : "', '"'));
                    if(!empty($channel)){
                        $checkExisting = Channel::where('title', 'LIKE', $channel)->whereRaw('LOWER(title) = ?', [strtolower($channel)])->first();
                        if(!$checkExisting){
                            $newChannel = Channel::create([
                                "title" => $channel,
                                "title_slug" => UtilsController::createSlug($channel, "channels", "title_slug"),
                                "status" => $grabber->status
                            ]);
                            $channel = $newChannel->id;
                        }
                        else {
                            $channel = $checkExisting->id;
                        }
                    }
                }
                if(!Video::where('source', 'LIKE', '%' . $viewkey[1] . '%')->exists() || $grabber->duplicated == 2){
                    $video = new Video([
                        "name" => strpos($grabber->data, "1") !== false ? $title : "New Import",
                        "description" => strpos($grabber->data, "2") !== false ? $description : " ",
                        "file" => "Grabber",
                        "type" => $grabber->type,
                        "status" => 2,
                        "source" => "PornHub.com;$url",
                        "likes" => strpos($grabber->data, "3") !== false ? $likes : 0,
                        "duration" => $duration,
                        "embed" => (int)$grabber->importType === 1 ? $embed : "",
                        "title_slug" => UtilsController::createSlug($title, "videos", "title_slug"),
                        "date_scheduled" => date('Y-m-d H:i:s')
                    ]);
                    $video->save(); 
                    if($categories){
                        $video->categories()->attach($categories);
                    }
                    if($tags){
                        $video->tags()->attach($tags);
                    }
                    if($models){
                        $video->models()->attach($models);
                    }
                    if($channel){
                        $video->channels()->attach($channel);
                    }
                    $id = hash("crc32", $video->id);
                    $slug = str_slug($title, "-");
                    $importedFile = new ImportedFile([
                        "slug" => "$id-$slug",
                        "url" => ""
                    ]);
                    $importedFile->save();
                    $progress->files()->save($importedFile);
                    if((int)$grabber->importType === 2){
                        $downloadCommand = "yt-dlp --merge-output-format mp4 -o '".base_path("public/videos/$id-$slug.mp4")."' $url";
                        exec($downloadCommand, $output, $returnCode);
                        //$this->resoultionConvert($id, $slug);
                        CreateThumbnailImages::dispatch("$id-$slug.mp4", "$id-$slug");
                        ConvertVideoResolution::dispatch($id, $slug);
                        $video->update([
                            "slug" => "$id-$slug",
                            "file" => "$id-$slug.mp4",
                            "status" => $grabber->status
                        ]);
                        if($duration === 0){
                            $command = "ffprobe -i \"".base_path("public/videos/$id-$slug.mp4")."\" -show_entries format=duration -v quiet -of csv=\"p=0\"";
                            $output = [];
                            exec($command, $output);
                            $duration = floatval($output[0]);
                            $video->update(["duration" => (int)$duration]);
                        }
                        //$videoFunctions = new VideoController;
                        //$videoFunctions->createThumbnails("$id-$slug.mp4", "$id-$slug");
                        CreateThumbnails::dispatch("$id-$slug.mp4", "$id-$slug");
                    }
                    else {
                        ob_start();
                        $formatListCommand = "yt-dlp --list-formats \"$url\"";
                        exec($formatListCommand, $formatsOutput);
                        $formatsOutput = implode(", ", $formatsOutput);
                        $formatsOutput = $this->parseData($formatsOutput, "hls-", " ");
                        $command = "yt-dlp --format \"hls-$formatsOutput\" -o \"".base_path("public/videos/$id-$slug.mp4")."\" \"$url\"";
                        exec($command, $output, $exitCode);
                        $formatsOutput = ob_get_clean();
                        $video->update([
                            "slug" => "$id-$slug",
                            "file" => "$id-$slug.mp4",
                            "status" => $grabber->status
                        ]);
                        //$videoFunctions = new VideoController;
                        //$videoFunctions->createThumbnails("$id-$slug.mp4", "$id-$slug");
                        CreateThumbnails::dispatch("$id-$slug.mp4", "$id-$slug", true);
                    }
                }
                $grabber->update(["new" => 0]);
                $grabber->decrement('queue', 1);
                
                //Update history log - successful
                $grabberItem->update([
                    "status" => 2
                ]);
            } catch (\Throwable  $e) {
                $grabber->update(["new" => 0]);
                $grabber->decrement('queue', 1);
                
                //Update history log - failed
                $grabberItem->update([
                    "status" => 3,
                    "message" => $e
                ]);
                
                continue;
            }
        } 
        return "ok";
    }

    public function xhamster(Request $request){
        $urls = preg_split("/\r\n|\r|\n/", $request->source);
        $grabber = Grabber::find($request->id);
        $grabber->update(["queue" => (int)$grabber->queue + count($urls)]);
        $progress = new ProgressImport;
        $progress->save();
        foreach($urls as $url){
            try {
                //Add to history log
                $grabberItem = new GrabberItem([
                    "grabber_id" => $grabber->id,
                    "url" => $url,
                    "message" => "",
                    "status" => 1
                ]);
                $grabberItem->save();
    
                $code = explode("-", $url);
                $code = end($code);
                $slug = explode("/", $url);
                $slug = end($slug);
                $embedUrl= "https://xhamster.com/embed/$code";
                $command = "timeout -k 10s 10s chromium-browser --no-sandbox --headless --disable-gpu --dump-dom --disable-software-rasterizer --disable-extensions $url";
                exec($command, $output, $returnVar);
                $source = implode(" ", $output);
                $embed = $embedUrl;
                $title = $this->parseData($source, '<title>', '|');
                $description = $this->parseData($source, '"description":"', '"');
                $duration = $this->parseData($source, 'duration":', ',', 12);
                $likes = $this->parseData($source, '<div class="rb-new__info" ', '/');
                $likes = $this->parseData($likes, '>', ' ');
                $likes = str_replace(",", "", $likes);
                $views= $this->parseData($source, ',"views":', ',');
                if($grabber->title_limit > 0){
                    $title = substr($title, 0, $grabber->title_limit);
                }
                if($grabber->description_limit > 0){
                    $description = substr($description, 0, $grabber->description_limit);
                }
                $categories = [];
                if(strpos($grabber->data, "8") !== false){
                    $categories = $this->parseData($source, '&videoCategory=', '&');
                    $categories = explode( "%2C", $categories );
                    foreach ($categories as &$category) {
                        $category = str_replace("+", " ", $category);
                        $checkExisting = ContentCategory::where('name', 'LIKE', $category)->whereRaw('LOWER(name) = ?', [strtolower($category)])->first();
                        if(!$checkExisting){
                            $newCategory = ContentCategory::create([
                                "name" => $category,
                                "status" => $grabber->status,
                                "title_slug" => UtilsController::createSlug($category, "content_categories", "title_slug")
                            ]);
                            $category = $newCategory->id;
                        }
                        else {
                            $category = $checkExisting->id;
                        }
                    }
                }
                $tags = [];
                if(strpos($grabber->data, "9") !== false){
                    $tags = explode("/tags\/", $source);
                    $tags = array_slice($tags, 1);
                    unset($tags[0]);
                    foreach ($tags as &$tag) {
                        $tag = explode('"', $tag);
                        $tag = str_replace("-", ' ', $tag[0]);
                        if(!empty($tag)){
                            $checkExisting = ContentTag::where('name', 'LIKE', $tag)->whereRaw('LOWER(name) = ?', [strtolower($tag)])->first();
                            if(!$checkExisting){
                                $request = new Request([
                                    "name" => $tag
                                ]);
                                $newTag = ContentTagController::store($request);
                                $tag = $newTag->original["data"]->id;
                            }
                            else {
                                $tag = $checkExisting->id;
                            }
                        }
                    }
                }
                $models = [];
                if(strpos($grabber->data, "5") !== false){
                    $models = explode('pornstars/', $source);
                    unset($models[0]);
                    foreach ($models as &$model) {
                        $model = $this->parseData($model, 'alt="', '"');
                        $model = str_replace("/\r\n|\r|\n/", "", $model);
                        if(!empty($model)){
                            $checkExisting = Model::where('stage_name', 'LIKE', $model)->whereRaw('LOWER(stage_name) = ?', [strtolower($model)])->first();
                            if(!$checkExisting){
                                $newModel = Model::create([
                                    "stage_name" => $model,
                                    "title_slug" => UtilsController::createSlug($model, "models", "title_slug")
                                ]);
                                $model = $newModel->id;
                            }
                            else {
                                $model = $checkExisting->id;
                            }
                        }
                    }
                }
                $channel = null;
                if(strpos($grabber->data, "7") !== false){
                    $channel = $this->parseData($source, 'channels/', '/a>');
                    $channel = $this->parseData($channel, 'alt="', '"');
                    if(!empty($channel)){
                        $checkExisting = Channel::where('title', 'LIKE', $channel)->whereRaw('LOWER(title) = ?', [strtolower($channel)])->first();
                        if(!$checkExisting && !empty($channel)){
                            $newChannel = Channel::create([
                                "title" => $channel,
                                "title_slug" => UtilsController::createSlug($channel, "channels", "title_slug"),
                                "status" => $grabber->status
                            ]);
                            $channel = $newChannel->id;
                        }
                        else {
                            $channel = $checkExisting->id;
                        }
                    }
                }
                if(!Video::where('source', 'LIKE', '%' . $url . '%')->exists() || $grabber->duplicated == 2){
                    $video = new Video([
                        "name" => strpos($grabber->data, "1") !== false ? $title : "New Import",
                        "description" => strpos($grabber->data, "2") !== false ? $description : " ",
                        "file" => "Grabber",
                        "type" => $grabber->type,
                        "status" => 2,
                        "views" => strpos($grabber->data, "4") !== false ? $views : 0,
                        "likes" => strpos($grabber->data, "3") !== false ? $likes : 0,
                        "source" => "XHamster.com;$url",
                        "duration" => $duration,
                        "embed" => (int)$grabber->importType === 1 ? $embed : "",
                        "title_slug" => UtilsController::createSlug($title, "videos", "title_slug"),
                        "date_scheduled" => date('Y-m-d H:i:s')
                    ]);
                    $video->save();  
                    if($categories){
                        $video->categories()->attach($categories);
                    }
                    if($tags){
                        $video->tags()->attach($tags);
                    }
                    if($models){
                        $video->models()->attach($models);
                    }
                    if($channel){
                        $video->channels()->attach($channel);
                    }
                    $id = hash("crc32", $video->id);
                    $slug = str_slug($title, "-");
                    $importedFile = new ImportedFile([
                        "slug" => "$id-$slug",
                        "url" => ""
                    ]);
                    $importedFile->save();
                    $progress->files()->save($importedFile);
                    if((int)$grabber->importType === 2){
                        $downloadCommand = "yt-dlp --merge-output-format mp4 -o '".base_path("public/videos/$id-$slug.mp4")."' $url";
                        exec($downloadCommand, $output, $returnCode);
                        //$this->resoultionConvert($id, $slug);
                        CreateThumbnailImages::dispatch("$id-$slug.mp4", "$id-$slug");
                        ConvertVideoResolution::dispatch($id, $slug);
                        $video->update([
                            "slug" => "$id-$slug",
                            "file" => "$id-$slug.mp4",
                            "status" => $grabber->status
                        ]);
                        //$videoFunctions = new VideoController;
                        //$videoFunctions->createThumbnails("$id-$slug.mp4", "$id-$slug");
                        CreateThumbnails::dispatch("$id-$slug.mp4", "$id-$slug");
                    }
                    else {
                        ob_start();
                        $formatListCommand = "yt-dlp --list-formats \"$url\"";
                        exec($formatListCommand, $formatsOutput);
                        $formatsOutput = implode(", ", $formatsOutput);
                        preg_match_all('/mp4-[a-zA-Z0-9]{3,4}p/', $formatsOutput, $matches);
                        $command = "yt-dlp --format \"".$matches[0][1]."\" -o \"".base_path("public/videos/$id-$slug.mp4")."\" \"$url\"";
                        exec($command, $output, $exitCode);
                        $formatsOutput = ob_get_clean();
                        $video->update([
                            "slug" => "$id-$slug",
                            "file" => "$id-$slug.mp4",
                            "status" => $grabber->status
                        ]);
                        //$videoFunctions = new VideoController;
                        //$videoFunctions->createThumbnails("$id-$slug.mp4", "$id-$slug");
                        CreateThumbnails::dispatch("$id-$slug.mp4", "$id-$slug", true);
                    }
                }
                $grabber->update(["new" => 0]);
                $grabber->decrement('queue', 1);
                
                //Update history log - successful
                $grabberItem->update([
                    "status" => 2
                ]);
            } catch(\Throwable $e){
                $grabber->update(["new" => 0]);
                $grabber->decrement('queue', 1);
                \Log::error("xHamster import error: $e");
                //Update history log - failed
                $grabberItem->update([
                    "status" => 3,
                    "message" => $e
                ]);
                
                continue;
            }
        } 
        return "ok";
    }

    public function progress() {
        $progress = ProgressImport::latest()->first();
        $localFileSize = 0;
        $i = 0;
        $file = $progress->files()->latest()->first();
        try {
            $files = glob(public_path("videos/*.part"));
            if (!empty($files)) {
                $localFileSize = filesize($files[0]);
                $message = "Downloading ".$file["slug"].".mp4 (".number_format(($localFileSize / 1024 / 1024), 3)." MB downloaded)...";
            } else {
                $localFileSize = filesize(public_path("videos/".$file["slug"].".mp4"));
                $message = "Converting and creating thumbnails, please wait...";
            }
        } catch (Exception $e) {
        } finally {
            return [
                "message" => $message,
                "progress" => 0,
                "files" => $files
            ];
        }
    }

    public function clearString($string) {
        $string = str_replace([
            '&nbsp;'
        ],
        [
            ' '
        ], $string);
        return $string;
    }

    public function resoultionConvert($id, $slug) {
        //Find resolution
        $videoPath = base_path("public/videos/$id-$slug.mp4");
        $ffprobeCommand = "ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0 $videoPath";
        // Prepare variables to capture output and return code
        $output = [];
        $returnVar = 0;
        // Execute the command
        $resolution = exec($ffprobeCommand, $output, $returnVar);
        // Check for errors
        if ($returnVar !== 0) {
            return [
                'error' => true,
                'message' => "Error executing command. Return code: $returnVar",
                'output' => implode("\n", $output)
            ];
        }
        list($width, $height) = explode(',', implode("\n", $output));

        //Conver to different formats
        if($height > 240){
            $outputPath = base_path("public/videos/$id-$slug-240p.mp4");
            $ffmpegCommand = "ffmpeg -i $videoPath -vf scale=426:240 -c:a copy $outputPath";
            exec($ffmpegCommand);
        }
        if($height > 480){
            $outputPath = base_path("public/videos/$id-$slug-480p.mp4");
            $ffmpegCommand = "ffmpeg -i $videoPath -vf scale=854:480 -c:a copy $outputPath";
            exec($ffmpegCommand);
        }
        if($height > 720){
            $outputPath = base_path("public/videos/$id-$slug-720p.mp4");
            $ffmpegCommand = "ffmpeg -i $videoPath -vf scale=1280:720 -c:a copy $outputPath";
            exec($ffmpegCommand);
        }
        if($height > 1080){
            $outputPath = base_path("public/videos/$id-$slug-1080p.mp4");
            $ffmpegCommand = "ffmpeg -i $videoPath -vf scale=1920:1080 -c:a copy $outputPath";
            exec($ffmpegCommand);
        }
        if($height > 2160){
            $outputPath = base_path("public/videos/$id-$slug-4k.mp4");
            $ffmpegCommand = "ffmpeg -i $videoPath -vf scale=3840:2160 -c:a copy $outputPath";
            exec($ffmpegCommand);
        }
        if($height > 4320){
            $outputPath = base_path("public/videos/$id-$slug-8k.mp4");
            $ffmpegCommand = "ffmpeg -i $videoPath -vf scale=7680:4320 -c:a copy $outputPath";
            exec($ffmpegCommand);
        }
    }

    public function resetQueue(){
        $grabbers = Grabber::where("queue", ">", 0)->get();
        foreach($grabbers as $grabber){
            $grabber->update([ "queue" => 0 ]);
        }    
    }
}