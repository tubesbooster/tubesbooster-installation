<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ImportController;
use App\Jobs\ConvertVideoResolution;
use App\Jobs\CreateThumbnails;
use App\Models\Video;
use App\Models\ContentCategory;
use App\Models\ContentTag;
use App\Models\VideoLike;
use App\Models\VideoView;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use FFMpeg\FFProbe;
use FFMpeg\FFMpeg;
use Pawlox\VideoThumbnail\Facade\VideoThumbnail;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Format\Video\X264;
use FFMpeg\Filters\Video\VideoFilters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    public static function index(Request $request)
    {
        $videos = Video::query();
        if($request->column === "views"){
            $videos->leftJoin('video_views', 'video_views.video_id', '=', 'videos.id')
                ->selectRaw('videos.*, (COALESCE(videos.views, 0) + COALESCE(COUNT(video_views.id), 0)) as total_views');
        }
        if($request["id"]){
            $videos->where('id', $request->id);
        }
        if($request["ids"]){
            $videos->whereIn('id', $request["ids"]);
        }
        if($request["name"]){
            $videos->where('name', 'LIKE', '%'.$request->name.'%');
        }
        if($request["categories"]){
            $categories = json_decode($request->categories);
            if(!empty($categories)){
                foreach ($categories as $category) {
                    $id = $category->id;
                    if($request->header('Show-All') !== "true"){
                        $category_by_slug = ContentCategory::where('title_slug', $id);
                        $id = $category_by_slug->id;
                    }
                    $videos->orWhereHas('categories', function ($subquery) use ($id) {
                        $subquery->where('content_category_id', $id);
                    });
                }
            }
        }
        if($request["users"] || $request["users"] === "0"){
            $users = json_decode($request->users);
            $usersArray = [];
            if(!empty($users)){
                foreach ($users as $key => $user) {
                    $usersArray[] = $user->id;
                }
                $videos->orWhereIn('user_id', $usersArray);
            }
        }
        if($request["user_id"]){
            $videos->orWhere('user_id', $request["user_id"]);
            if($request["user_id"] == 1){
                $videos->orWhereNull('user_id')->orWhere('user_id', 0);
            }
        }
        if($request["description"]){
            $videos->where('description', 'LIKE', '%'.$request->description.'%');
        }
        if($request["duration"]){
            $duration = json_decode($request->duration);
            if((int)$duration[0] !== 0){
                $videos->whereRaw('CAST(duration AS UNSIGNED) >= ?', [$duration[0]*60]);
            }
            $videos->where(function ($query) use ($duration) {
                $query->whereRaw('CAST(duration AS UNSIGNED) <= ?', [$duration[1]*60])
                      ->orWhereNull('duration');
            });
        }
        if($request["status"]){
            $videos->where('status', $request["status"]);
        }
        if($request["type"]){
            $videos->where('type', $request["type"]);
        }
        if($request["date_from"] && $request["date_from"] !== "null" ){
            $date = str_replace('"', '', explode("T", $request["date_from"])[0]);
            $videos->where('created_at', '>=', $date);
        }
        if($request["date_to"] && $request["date_to"] !== "null"){
            $date = str_replace('"', '', explode("T", $request["date_to"])[0]);
            $videos->where('created_at', '<=', $date);
        }
        if($request["model_id"]){
            $modelId = $request["model_id"];
            $videos->whereHas('models', function ($query) use ($modelId) {
                $query->where('models.id', $modelId);
            });
        }
        if($request["channel_id"]){
            $channelId = $request["channel_id"];
            $videos->whereHas('channels', function ($query) use ($channelId) {
                $query->where('channels.id', $channelId);
            });
        }
        if($request["category_id"] && $request["category_id"] !== "all"){
            $categoryId = $request["category_id"];
            $videos->whereHas('categories', function ($query) use ($categoryId) {
                $query->where('content_categories.id', $categoryId);
            });
        }
        if($request["tag_id"] && $request["tag_id"] !== "all"){
            $tagId = $request["tag_id"];
            $videos->whereHas('tags', function ($query) use ($tagId) {
                $query->where('content_tags.id', $tagId);
            });
        }
        if($request->header('Show-All') != true){
            $videos->where('date_scheduled', '<', Carbon::now()->addHours(4)->toDateTimeString());
            $videos->where('status', 1);
        }

        //Load default if undefined properly
        $request->order = $request->order !== "desc" ? "asc" : "desc";
        $request->limit = $request->limit === "undefined" || empty($request->limit) ? 10 : (int)$request->limit;
        $request->column = $request->column === "undefined" || empty($request->column) ? "id" : $request->column;

        switch($request->column){
            case "title":
                $orderColumn = "name";
                break;
            case "published":
                $orderColumn = "created_at";
                break;
            case "scheduled":
                $orderColumn = "date_scheduled";
                break;
            default:
                $orderColumn = $request->column;
                break;
        }

        if($request->column === "duration"){
            $collection = $videos->orderByRaw("CAST($orderColumn AS SIGNED) $request->order");
        }
        elseif($request->column === "views"){
            $collection = $videos->groupBy('videos.id', 'videos.views')
                ->orderBy('total_views', $request->order);
        }
        else  {
            $collection = $videos->orderBy($orderColumn, $request->order); 
        }
        $collection = $videos->with(['channels' => function ($query) {
            $query->select('channels.id', 'title as name');
        }]);
        $collection = $videos->with(['models' => function ($query) {
            $query->select('models.id', 'stage_name as name');
        }]);

        //Get ads for frontend
        $ad3 = array();
        $ad6 = array();
        if($request->header('Show-All') != true && $request->disableAds !== true){
            $categoriesArray= array();
            if(isset($request->category_id)){
                $categoriesArray[] = [
                    "id" => $request->category_id
                ];
            }
            $ad3 = AdController::showFrontend($categoriesArray, 3, 1);
            $ad6 = AdController::showFrontend($categoriesArray, 6, 1);
        }

        $collection = $videos->paginate(isset($ad3[0]) ? $request->limit - 1 : $request->limit); 
        //$collection = $videos->paginate(1); 
        $meta = [
            "categories" => ContentCategory::get(),
            "tags" => ContentTag::get()
        ];
        foreach($collection as $key => $video){
            $status = "";
            switch( $video->status ){
                case 1: $status = "Online"; 
                    break;
                case 2: $status = "Offline";
                    break;
                default: $status = "Online";
            }
            $type = "";
            switch( $video->type ){
                case 1: $type = "Public"; 
                    break;
                case 2: $type = "Private";
                    break;
                case 3: $type = "Premium";
                    break;
                default: $type = "Public";
            }
            $seconds = $video->duration ? $video->duration : 0;
            $minutes = 0;
            $hours = 0;
            if($seconds > 60){
                $minutes = (int)((int)$seconds / 60);
                $seconds = (int)$seconds - $minutes * 60;
            }
            if($minutes > 60){
                $hours = (int)($minutes / 60);
                $minutes = $minutes - $hours * 60;
            }
            $length = sprintf('%02d', $hours).":".sprintf('%02d', $minutes).":".sprintf('%02d', $seconds);
            $user = User::find($video->user_id ? $video->user_id : 1);
            $models = $video->models()->get()->toArray();
            foreach($models as &$model){
                $model = $model["stage_name"];
            }
            $models = implode(", ", $models);
            
            // Check if video is vertical
            $vertical = false;
            $imagePath = public_path("videos/thumbs/".$video->slug.".jpg");
            if (file_exists($imagePath)) {
                $imageSize = getimagesize($imagePath);
                if ($imageSize) {
                    $width = $imageSize[0]; 
                    $height = $imageSize[1];
                    if($height > $width){
                        $vertical = true;
                    }
                }
            }
            
            // Get collection
            $collection[$key] = [
                'id' => $video->id,
                'thumb' => file_exists(public_path("videos/thumbs/".$video->slug.".jpg")) ? "videos/thumbs/".$video->slug.".jpg?t=".time() : "no-preview",
                'title' => htmlspecialchars_decode($video->name, ENT_XML1),
                'user' => $user ? $user->username : "",
                'userAvatar' => $user ? "avatars/".$user->avatar : null,
                'status' => $status,
                'type' => $type,
                'duration' => $length,
                'rating' => 5,
                'embed' => $video->embed ? "Embeded" : "Downloaded",
                'views' => $video->views()->count() + $video->views,
                'likes' => $video->likes ? $video->likes : 0,
                'comments' => 0,
                'channels' => $video->channels,
                'models' => $video->models,
                'categories' => $video->categories,
                'published' => date("d.m.Y H:i", strtotime($video->created_at)),
                'scheduled' => date("d.m.Y H:i", strtotime($video->date_scheduled)),
                'scheduled2' => $video->date_scheduled,
                'model' => $models,
                'title_slug' => $video->title_slug,
                'vertical' => $vertical
            ];
        }
        
        $allVideos = new Video;
        $collectionArray = $collection->toArray();

        //Get ads for frontend
        if(isset($ad3[0])){
            if (count($collectionArray["data"]) >= 3) {
                array_splice($collectionArray["data"], 3, 0, [$ad3[0]]); // Insert after the 3rd entry
            } else {
                $collectionArray["data"][] = $ad3[0]; // Append to the end if less than 3 entries
            }
        }

        return response()->json(array_merge($collectionArray, [
            "meta" => [
                "maxDuration" => ceil((int)Video::max(DB::raw('CAST(duration AS SIGNED)')) / 60)
            ],
            "ad6" => $ad6
        ]));
    }
    
    public function store(Request $request)
    {
        if($request->date_scheduled){
            $date = new \DateTime($request->date_scheduled);
            $formattedDate = $date->format('Y-m-d H:i');
            $request["date_scheduled"] = $formattedDate;
        }
        if($request->id){
            $video = Video::find($request->id);
            $video->categories()->detach();
            $video->tags()->detach();
            $video->channels()->detach();
            $video->models()->detach();
            if($request->categoriesNew && count($request->categoriesNew) > 0){
                $categoriesIds = ContentCategoryController::getCategoriesId($request->categoriesNew);
                $video->categories()->attach($categoriesIds);
            }
            if($request->tagsNew && count($request->tagsNew) > 0){
                $tagIds = ContentTagController::getTagsId($request->tagsNew);
                $video->tags()->attach($tagIds);
            }
            if($request->channels && count($request->channels) > 0){
                foreach ($request->channels as $channel) {
                    $video->channels()->attach($channel);
                }
            }
            if($request->models && count($request->models) > 0){
                foreach ($request->models as $model) {
                    $video->models()->attach($model);
                }
            }

            $video->update(array_merge(
                $request->only(["name", "description", "status", "type", "user_id", "date_scheduled"]),
                [
                    "title_slug" => UtilsController::createSlug(
                        $video->name, 
                        "videos", 
                        "title_slug",
                        $video->id
                    )
                ]
            ));
        }
        else {
            $video = new Video($request->only(["name", "description", "file", "status", "type", "duration", "user_id", "date_scheduled"]));
            $video->title_slug = UtilsController::createSlug(
                    $request->name, 
                    "videos", 
                    "title_slug",
                    $video->id
            );
            $video->save();   
            if($request->categoriesNew && count($request->categoriesNew) > 0){
                $categoriesIds = ContentCategoryController::getCategoriesId($request->categoriesNew);
                $video->categories()->attach($categoriesIds);
            }
            if($request->tagsNew && count($request->tagsNew) > 0){
                $tagIds = ContentTagController::getTagsId($request->tagsNew);
                $video->tags()->attach($tagIds);
            }
            if($request->channels && count($request->channels) > 0){
                foreach ($request->channels as $channel) {
                    $video->channels()->attach($channel);
                }
            }
            if($request->models && count($request->models) > 0){
                foreach ($request->models as $model) {
                    $video->models()->attach($model);
                }
            }
            $id = hash("crc32", $video->id);
            $slug_res = str_slug("$video->name", "-");
            $slug = str_slug("$id-$video->name", "-");
            $ext = explode(".", $request->file->getClientOriginalName());
            $filename = $slug.".".end($ext);
            $request->file->move(public_path("videos") ,$filename);

            //$this->createThumbnails($filename, $slug);
            CreateThumbnails::dispatch($filename, $slug);
            //Get Video Duration
            $ffprobe = FFProbe::create();
            $videoFile = $ffprobe
                ->streams(public_path("videos/$filename"))
                ->videos()                      
                ->first();    
            $duration = (int)$videoFile->get('duration');
            
            //$resolutionConvert = ImportController::resoultionConvert($id, $slug_res);
            ConvertVideoResolution::dispatch($id, $slug_res);
            $video->update([
                "slug" => $slug,
                "file" => $filename,
                "duration" => $duration
            ]);
        }
        return response()->json(['data' => $video]);
    }

    public static function show($id, Request $request)
    {
        $video = array();
        $users = User::get()->toArray();
        foreach($users as $userId => $user){
            $users[$userId]["id"] = $user["id"];
            $users[$userId]["name"] = $user["username"];
        }
        $meta = [
            "categories" => ContentCategory::get(),
            "tags" => ContentTag::get(),
            "user_id" => $users
        ];
        if($id !== "new" && $id !== 0){
            if(ctype_digit($id)){
                $video = Video::where('id', (int)$id);
            }
            else {
                $video = Video::where('title_slug', $id);
                if($request->header('Show-All') != true){
                    $video->where('status', 1);
                }
            }
            $video = $video->first();

            if (!$video) {
                abort(404);
            }

            // Get client IP address
            $ip = $_SERVER['REMOTE_ADDR'];
            // Check if a record with the same video_id and ip exists
            $existingView = VideoView::where('video_id', $video->id)->where('ip', $ip)->first();
            // If no existing record found, create a new one
            if (!$existingView) {
                // Create a new entry in the video_views table
                $newView = new VideoView();
                $newView->video_id = $video->id;
                $newView->ip = $ip;
                $newView->save();
            }
            $vertical = false;
            try {
                if(empty($video->embed)){
                    $ffprobe = FFProbe::create();
                    $videoFile = $ffprobe
                        ->streams(public_path("videos/$video->file"))
                        ->videos()                      
                        ->first();              
                    $width = $videoFile->getDimensions()->getWidth();
                    $height = $videoFile->getDimensions()->getHeight();
                    $fps = number_format(($videoFile->get('nb_frames') / $videoFile->get('duration')), 2);
                    if($width < $height){
                        $vertical = true;
                    }
                }
                else {
                    $width = 0;
                    $height = 0;
                    $fps = 0;
                }
            } catch(\Throwable $e){
                $width = 0;
                $height = 0;
                $fps = 0;
            }
            $resolution = [ "" => "Original ($width x $height)" ];
            if($height > 2160){ $resolution["-4K"] = "4K (3840 x 2160)"; }
            if($height > 1080){ $resolution["-1080p"] = "1080p (1920 x 1080)"; }
            if($height > 720){ $resolution["-720p"] = "720p (1280 x 720)"; }
            if($height > 480){ $resolution["-480p"] = "480p (854 x 480)"; }
            if($height > 240){ $resolution["-240p"] = "240p (426 x 240)"; }
            $seconds = $video->duration ? $video->duration : 0;
            $minutes = 0;
            $hours = 0;
            if($seconds > 60){
                $minutes = ((int)$seconds / 60);
                $seconds = (int)$seconds - $minutes * 60;
            }
            if($minutes > 60){
                $hours = (int)($minutes / 60);
                $minutes = $minutes - $hours * 60;
            }
            $length = sprintf('%02d', $hours).":".sprintf('%02d', $minutes).":".sprintf('%02d', $seconds);

            $user = User::find($video->user_id ? $video->user_id : 1);
            $username = $user ? $user->display_name : "Admin";
            $words = str_word_count($username, 1);
            $userInitials = array_map(function($word) {
                return substr($word, 0, 1);
            }, $words);
            $userInitials = implode('', $userInitials);
            $models = $video->models()->select('models.id', 'models.name', 'models.thumbnail', 'models.stage_name', 'models.title_slug')->get();
            foreach ($models as $key => $entry) {
                $models[$key] = [
                    'id' => $entry->id,
                    'name' => $entry->stage_name ? $entry->stage_name : $entry->name,
                    'title_slug' => $entry->title_slug,
                    'avatar' => asset("avatars/".$entry->thumbnail)
                ];
            }

            $words = explode(' ', $video->name);
            $categoryIds = $video->categories()->pluck('id')->unique();
            $modelIds = $video->models()->pluck('models.id')->unique();

            // Fetch related videos
            $related = Video::with(['categories', 'models'])
                ->where('id', '!=', $video->id)
                ->where('status', 1)
                ->where(function($q) use ($words, $categoryIds, $modelIds) {
                    $q->where(function($q) use ($words) {
                        foreach ($words as $word) {
                            $q->orWhere('name', 'like', "%{$word}%");
                        }
                    })
                    ->orWhereHas('categories', function($q) use ($categoryIds) {
                        $q->whereIn('id', $categoryIds);
                    })
                    ->orWhereHas('models', function($q) use ($modelIds) {
                        $q->whereIn('models.id', $modelIds);
                    });
                })
                ->get(["id", "name", "slug", "user_id", "title_slug", "views"]);

            // Calculate and sort by relevance score
            $related = $related->map(function($video) use ($words, $categoryIds, $modelIds) {
                $score = 0;

                // Increase score based on model relevance
                foreach ($video->models as $model) {
                    if ($modelIds->contains($model->id)) {
                        $score += 2;
                        break; // If at least one model matches, increase score
                    }
                }

                // Increase score based on category relevance
                foreach ($video->categories as $category) {
                    if ($categoryIds->contains($category->id)) {
                        $score += 1;
                        break; // If at least one category matches, increase score
                    }
                }

                // Increase score based on title relevance
                foreach ($words as $word) {
                    if (stripos($video->name, $word) !== false) {
                        $score += 1;
                    }
                }

                // Check if video is vertical
                $verticalRelated = false;
                $imagePath = public_path("videos/thumbs/".$video->slug.".jpg");
                if (file_exists($imagePath)) {
                    $imageSize = getimagesize($imagePath);
                    if ($imageSize) {
                        $width = $imageSize[0]; 
                        $height = $imageSize[1];
                        if($height > $width){
                            $verticalRelated = true;
                        }
                    }
                }

                // Attach score to the video
                $video->score = $score;
                $video->score = htmlspecialchars_decode($video->name, ENT_XML1);
                $video->thumb = file_exists($imagePath) ? "videos/thumbs/".$video->slug.".jpg?t=".time() : "no-preview";
                $video->views = $video->views()->count() + $video->views;
                $user = User::find($video->user_id ? $video->user_id : 1);
                $video->user = $user->username;
                $video->userAvatar = "avatars/".$user->avatar;
                $video->title = $video->name;
                $video->vertical = $verticalRelated;
                
                return $video;
            });
            
            // Convert to an array to sort by score
            $relatedArray = $related->toArray();

            // Sort by score
            usort($relatedArray, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            // Take the top 16
            $topRelated = array_slice($relatedArray, 0, 8);

            // Convert back to a collection if necessary
            $topRelated = collect($topRelated);

            // Get information about current client like
            $videoLike = $video->likes()->select("type")->where("video_likes.ip_address", $request->ip())->first();
            if($videoLike){
                $videoLike->isLiked = true;
            }
            else {
                $videoLike = [
                    "isLiked" => false,
                    "type" => 0
                ];
            }

            //Get ads for frontend
            $ad1 = array();
            $ad2 = array();
            $ad4 = array();
            $ad5 = array();
            if($request->header('Show-All') != true){
                //dd($video->categories->toArray());
                $ad1 = AdController::showFrontend($video->categories->toArray(), 1, 1);
                $ad2 = AdController::showFrontend($video->categories->toArray(), 2, 1);
                $ad4 = AdController::showFrontend($video->categories->toArray(), 4, 3);
                $ad5 = AdController::showFrontend($video->categories->toArray(), 5, 1);
                $categories = $video->categories()->select('id', 'name', 'title_slug')->where("status", '!=', 2)->get();
                $tags = $video->tags()->select('id', 'name', 'title_slug')->where('status', '!=', 2)->get();
            }
            else {
                $categories = $video->categories()->select('id', 'name', 'title_slug')->get();
                $tags = $video->tags()->select('id', 'name', 'title_slug')->get();
            }

            return response()->json([
                'id' => $video->id,
                'name' => htmlspecialchars_decode($video->name, ENT_XML1),
                'description' => htmlspecialchars_decode($video->description, ENT_XML1),
                'status' => (int)$video->status,
                'type' => (int)$video->type,
                
                //deprecated
                'categories' => $video->categories()->pluck('id')->toArray(),
                
                'categoriesNew' => $categories,
                'file' => $video->file,
                
                //deprecated
                'tags' => $video->tags()->pluck('id')->toArray(),

                'tagsNew' => $tags,
                'channels' => $video->channels()->select('channels.id', 'channels.title as name')->get(),
                'models' => $models,
                'meta' => $meta,
                'length' => $length,
                'lengthRaw' => (int)$video->duration,
                'views' => ($video->views()->count() + $video->views),
                'likes' => $video->likes()->where("type", 0)->count() + (int)$video->likes,
                'dislikes' => $video->likes()->where("type", 1)->count(),
                'clientLike' => $videoLike,
                'fps' => $fps,
                'date_scheduled' => $video->date_scheduled,
                'datePublished' => date("d.m.Y H:i", strtotime($video->created_at)),
                'thumbnail' => file_exists(public_path("videos/thumbs/".$video->slug.".jpg")) ? $video->slug.".jpg" : "no-preview",
                'videoData' => [
                    'dimensions' => "$width x $height",
                    'resolution' => $resolution
                ],
                'source' => $video->source,
                'embed' => $video->embed,
                'user' => $username,
                'userAvatar' => $user ? "avatars/".$user->avatar : null,
                'userInitials' => $userInitials,
                'userCreatedAt' => $user ? date("m/d/Y", strtotime($user->created_at)) : "-",
                'user_id' => $video->user_id ? (int)$video->user_id : 1,
                'related' => $topRelated,
                'channel' => $video->channels()->first(),
                'ad1' => $ad1,
                'ad2' => $ad2,
                'ad4' => $ad4,
                'ad5' => $ad5,
                'vertical' => $vertical,
                'title_slug' => $video->title_slug
            ]);
        }
        return response()->json([
            'meta' => $meta
        ]);
    }

    public function showPreview($id, $hash, Request $request){
        return $this->show($id, $request);
    }

    public function delete(Request $request)
    {
        $ids = explode(",", $request->items);
        foreach($ids as $item){
            $video = Video::find($item);
            $video->delete();
            $file = public_path('videos/'.$video->file);
            if (file_exists($file)) {
                unlink($file);
            }
            $formats = array('240p', '480p', '720p', '1080p', '4k', '8k');
            foreach($formats as $format){
                $fileFormat = str_replace('.mp4', '-' . $format . '.mp4', $file);
                if (file_exists($fileFormat)) {
                    unlink($fileFormat);
                }
            }
        }
        return response()->json($video);
    }

    public function updateThumbnail(Request $request)
    {
        $ffmpeg = FFMpeg::create();
        $videoPath = public_path('videos')."/".$request->videoFile;
        $thumbPath = public_path('videos/thumbs')."/".str_replace(".mp4", ".jpg", $request->videoFile);
        $currentTime = $request->currentTime;

        Log::info("Video Path: ".$videoPath);
        Log::info("Thumbnail Path: ".$thumbPath);
        Log::info("Current Time: ".$currentTime);

        // Check if file exists
        if (file_exists($thumbPath)) {
            unlink($thumbPath); // Delete the existing file
        }

        try {
            $videoFile = $ffmpeg->open($videoPath);
            $frame = $videoFile->frame(TimeCode::fromSeconds($currentTime));
            $frame->save($thumbPath);

            return response()->json(["thumbnail" => str_replace(".mp4", ".jpg", $request->videoFile)]);
        } catch (\Exception $e) {
            Log::error("FFMpeg Error: ".$e->getMessage());
            return response()->json(["error" => $e->getMessage()], 500);
        }

        return response()->json([
            "thumbnail" => str_replace(".mp4", ".jpg", $request->videoFile)
        ]);
    }

    public function customThumbnail(Request $request)
    {
        $request->file->move(public_path("videos/thumbs") ,$request->thumbnail);
        return response()->json([
            "thumbnail" => $request->thumbnail
        ]);
    }

    public function bulkEdit(Request $request)
    {
        $videos = explode(",", $request->selected);
        foreach($videos as $videoId){
            $video = Video::find($videoId);
            if($request->categories && count($request->categories) > 0){
                $video->categories()->syncWithoutDetaching($request->categories);
            }
            if($request->tags && count($request->tags) > 0){
                $video->tags()->syncWithoutDetaching($request->tags);
            }
            if($request->models && count($request->models) > 0){
                $video->models()->syncWithoutDetaching($request->models);
            }
            if($request->channels && count($request->channels) > 0){
                $video->channels()->syncWithoutDetaching($request->channels);
            }
            $requestData = $request->only(["status", "type", "date_scheduled"]);
            $requestData["status"] = $requestData["status"] == 0 ? $video->status : $requestData["status"];
            $requestData["type"] = $requestData["type"] == 0 ? $video->type : $requestData["type"];
            if($requestData["date_scheduled"] == "null"){
                $requestData["date_scheduled"] = $video->date_scheduled;
            }
            $video->update($requestData);      
        }
    }

    public function like(Request $request){
        $videoLike = VideoLike::where("ip_address", $request->ip())
            ->where("video_id", $request->id)
            ->first();
        if($videoLike){
            if($videoLike->type === (int)$request->type){
                $videoLike->forceDelete();    
            }
            else {
                $videoLike->update([
                    "type" => (int)$request->type
                ]);
            }
        }
        else {
            $videoLike = VideoLike::create([
                "video_id" => $request->id,
                "ip_address" => $request->ip(),
                "type" => $request->type
            ]);
            $videoLike->save();
        }
    }

    public function createThumbnails($filename, $slug)
    {
        //Create animated thumbnail
        $ffmpeg = FFMpeg::create();
        $ffprobe = FFProbe::create();
        $videoMeta = $ffprobe
            ->streams(public_path("videos/$filename"))
            ->videos()                      
            ->first();         
        $duration = $videoMeta->get('duration');
        $videoFile = $ffmpeg->openAdvanced([public_path("videos/$filename")]);
        $videoFile->filters()
            ->custom('[0:v]', 'trim=start='.($duration/5).':end='.(($duration/5) + 2).',setpts=PTS-STARTPTS', '[a]')
            ->custom('[0:v]', 'trim=start='.($duration/5*2).':end='.(($duration/5*2) + 2).',setpts=PTS-STARTPTS', '[b]')
            ->custom('[a][b]', 'concat', '[c]')
            ->custom('[0:v]', 'trim=start='.($duration/5*3).':end='.(($duration/5*3) + 2).',setpts=PTS-STARTPTS', '[d]')
            ->custom('[0:v]', 'trim=start='.($duration/5*4).':end='.(($duration/5*4) + 2).',setpts=PTS-STARTPTS', '[f]')
            ->custom('[d][f]', 'concat', '[g]')
            ->custom('[c][g]', 'concat', '[out1]')
            ->custom('[out1]', 'scale=320:240', '[out2]')
        ;
        $videoFile->map(['[out2]'], new X264(), public_path("videos/thumbs/preview-$slug.mp4"))->save();
        //Create thumbnail
        $videoFile = $ffmpeg->open(public_path('videos')."/$filename");
        $videoFile->frame(TimeCode::fromSeconds($duration/2))->save(public_path('videos/thumbs')."/$slug.jpg");
    }
}
