<?php

namespace App\Http\Controllers;
set_time_limit(0);

use Illuminate\Http\Request;
use App\Models\Video;
use App\Models\ProgressImport;
use App\Models\ImportedFile;
use App\Models\Grabber;
use YouTube\YouTubeDownloader;
use YouTube\Exception\YouTubeException;
use Pawlox\VideoThumbnail\Facade\VideoThumbnail;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use App\Http\Controllers\VideoController;
use Cregennan\PornhubToolkit\Toolkit as PHToolkit;
use GuzzleHttp\Client;
use Symfony\Component\Panther\Client as ChromeClient;
use Symfony\Component\DomCrawler\Crawler;
use Facebook\WebDriver\Remote\DesiredCapabilities;


use App\Models\Album;
use App\Http\Controllers\AlbumController;
use Illuminate\Http\UploadedFile;


class AlbumImportController extends Controller
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

    public function pornhub(Request $request) {
        $url = $request->url;
        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute cURL session and fetch the page source
        $pageSource = curl_exec($ch);
        return $pageSource;
        if ($pageSource !== false) {
            // Output or manipulate the page source as needed
        } else {
            echo 'Failed to retrieve the page source.';
        }
        // Close cURL session
        curl_close($ch);
        $title = $this->parseData($pageSource, '<meta name="twitter:title" content="', '"');
        $album = new Album(["name" => $title]);
        $album->save();   
        
        $photos = explode('"imageURL":"', $pageSource);
        $albumFunctions = new AlbumController;
        foreach($photos as $i => $photo){
            if($i > 0){
                $photoUrl = str_replace("\/", "/", explode('"', $photo)[0]);
                $photoArray[] = $photoUrl;
                $photoData = $albumFunctions->createPhoto();
                $filename = $photoData->slug.".jpg";
                $imageData = file_get_contents($photoUrl);
                $destinationPath = public_path("photos/$filename");
                $saveResult = file_put_contents($destinationPath, $imageData);
                $album->photos()->attach($photoData->id);
            }
        }
        return $album;
    }

    public function xhamster(Request $request) {
        //phpinfo();
        $urls = preg_split("/\r\n|\r|\n/", $request->url);
        foreach($urls as $url){
            // Initialize cURL session
            $ch = curl_init();

            // Set cURL options
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Execute cURL session and fetch the page source
            $pageSource = curl_exec($ch);

            if ($pageSource !== false) {
                // Output or manipulate the page source as needed
            } else {
                echo 'Failed to retrieve the page source.';
            }
            // Close cURL session
            curl_close($ch);
            $title = $this->parseData($pageSource, '<meta name="twitter:title" content="', '"');
            $title = str_replace([" | xHamster"], "", $title);
            $title = preg_replace('/ - \d{1,3} Pics$/', '', $title);
            $title = preg_replace('/: \d{1,3} Nude Pics$/', '', $title);
            $description = $this->parseData($pageSource, '"description":"', '"');
            $album = new Album([
                "name" => $title, 
                "type" => 1, 
                "status" => 1, 
                "description" => $description, 
                "source" => "XHamster.com", 
                "url" => $url,
                "title_slug" => UtilsController::createSlug($title, "albums", "title_slug")
            ]);
            $album->save();   

            $pageUrls = explode('"pageURL":"', $pageSource);
            $albumId = explode('-', $url);
            $albumId = end($albumId);
            
            $albumFunctions = new AlbumController;
            $photoArray = [];
            foreach($pageUrls as $i => $pageUrl){
                if($i > 0){
                    $imageUrl = explode('"imageURL":"', $pageUrl);
                    $imageUrl = str_replace("\/", "/", explode('"', $imageUrl[1])[0]);
                    $pageUrl = str_replace("\/", "/", explode('"', $pageUrl)[0]);
                    if(str_contains($pageUrl, $albumId) && !in_array($imageUrl, $photoArray)){
                        $photoArray[] = $imageUrl;
                        $photoData = $albumFunctions->createPhoto();
                        $filename = $photoData->slug.".jpg";
                        $imageData = file_get_contents($imageUrl);
                        $destinationPath = public_path("photos/$filename");
                        $saveResult = file_put_contents($destinationPath, $imageData);
                        $album->photos()->attach($photoData->id);
                    }
                }
            }
        }
        return $album;
    }

    public function xvideos(Request $request) {
        set_time_limit(0);
        $urls = preg_split("/\r\n|\r|\n/", $request->url);
        foreach($urls as $url){
            // Initialize cURL session
            $ch = curl_init();

            // Set cURL options
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Execute cURL session and fetch the page source
            $pageSource = curl_exec($ch);

            if ($pageSource !== false) {
                // Output or manipulate the page source as needed
            } else {
                echo 'Failed to retrieve the page source.';
            }
            // Close cURL session
            curl_close($ch);
            $title = $this->parseData($pageSource, '"title":"', '"');
            $description = $this->parseData($pageSource, 'prof-tab-metadata">', '</div>');
            if(strpos($description, '<br>')){
                $description = $this->parseData($description, '<p>', '<br>');
                $description = trim($description);    
            }
            else {
                $description = "";    
            }
            $album = new Album([
                "name" => $title, 
                "description" => $description,
                "type" => 1, 
                "status" => 1, 
                "source" => "XVideos.com", 
                "url" => $url,
                "title_slug" => UtilsController::createSlug($title, "albums", "title_slug")
            ]);
            $album->save();   
            
            $photos = explode('<a class="embed-responsive-item"', $pageSource);
            $albumFunctions = new AlbumController;
            foreach($photos as $i => $photo){
                if($i > 0){
                }
                if($i > 0){
                    $photoUrl = explode('"', explode('href="', $photo)[1])[0];
                    $photoArray[] = $photoUrl;
                    $photoData = $albumFunctions->createPhoto();
                    $filename = $photoData->slug.".jpg";
                    $imageData = file_get_contents($photoUrl);
                    $destinationPath = public_path("photos/$filename");
                    $saveResult = file_put_contents($destinationPath, $imageData);
                    $album->photos()->attach($photoData->id);
                }
            }
        }
        return $album;
    }
}