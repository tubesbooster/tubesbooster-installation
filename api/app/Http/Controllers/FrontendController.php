<?php

namespace App\Http\Controllers;

use App\Http\Controllers\VideoController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\ModelController;
use App\Models\Video;
use App\Models\Album;
use App\Models\ContentCategory;
use App\Models\Channel;
use App\Models\SiteModel as Model;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FrontendController extends Controller
{
    public function sidebar()
    {
        $requestChannels = new Request([
            "column" => "views",
            "order" => "desc",
            "limit" => 6
        ]);
        $responseChannels = (new ChannelController())->index($requestChannels);
        $responseArrayChannels = json_decode($responseChannels->getContent(), true);
        $dataChannels = $responseArrayChannels['data'] ?? [];

        return response()->json([
            "categories" => ContentCategory::withCount(['videos' => function ($query) {
                    $query->where('status', 1);
                }])
                ->where('status', 1)
                ->orderBy('videos_count', 'desc')
                ->orderBy('name', 'asc')
                ->limit(7)
                ->get()
                ->map(function ($category) {
                    $category->name = ucwords($category->name);
                    return $category;
                }),
            "categoriesTest" => ContentCategory::withCount(['videos' => function ($query) {
                    $query->where('status', 1);
                }])
                ->where('status', 1)
                ->orderBy('videos_count', 'desc')
                ->orderBy('name', 'asc')
                ->skip(7)
                ->take(8)
                ->get()
                ->map(function ($category) {
                    $category->name = ucwords($category->name);
                    return $category;
                }),
            "categoriesMore" => ContentCategory::withCount(['videos' => function ($query) {
                    $query->where('status', 1);
                }])
                ->where('status', 1)
                ->orderBy('videos_count', 'desc')
                ->skip(7)
                ->take(8)
                ->get()
                ->map(function ($category) {
                    $category->name = ucwords($category->name);
                    return $category;
                }),
            "channels" => $dataChannels,
            "models" => Model::select("id", "stage_name", "thumbnail")->take(6)->get(),
            "users" => User::select("id", "display_name", "avatar")->take(6)->get(),
        ]);
    }
    public function home()
    {
        $request = new Request;
        $request["limit"] = 8;
        $request["order"] = "desc";
        $request["column"] = "date_scheduled";
        
        $videosBeingWatchedNowIds = DB::table('video_views')
            ->join('videos', 'video_views.video_id', '=', 'videos.id')
            ->select('video_views.video_id')
            ->where('date_scheduled', '<', 'now()')
            ->where('videos.status', 1)
            ->groupBy('video_views.video_id')
            ->orderByRaw('MAX(video_views.id) DESC')
            ->take(8)
            ->pluck('video_id')
            ->toArray();
        $videosBeingWatchedNowIds = array_map('intval', $videosBeingWatchedNowIds);
        $idsList = implode(',', $videosBeingWatchedNowIds);
        
        if ($idsList) {
            $videosBeingWatchedNow = Video::whereIn('videos.id', $videosBeingWatchedNowIds)
                ->orderByRaw("FIELD(videos.id, $idsList)")
                ->get();
        }
        
        else {
            $videosBeingWatchedNow = array();
        }

        foreach($videosBeingWatchedNow as &$video){
            $user = User::find($video->user_id ? $video->user_id : 1);
            $video['thumb'] = file_exists(public_path("videos/thumbs/".$video->slug.".jpg")) ? "videos/thumbs/".$video->slug.".jpg?t=".time() : "no-preview";
            $video['user'] = $user ? $user->username : "";
            $video['userAvatar'] = $user ? "avatars/".$user->avatar : null;
            $video['views'] = $video->views()->count() + $video->views;
            $video['title'] = htmlspecialchars_decode($video->name, ENT_XML1);

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
            $video['duration'] = sprintf('%02d', $hours).":".sprintf('%02d', $minutes).":".sprintf('%02d', $seconds);
        }

        $videosNew = VideoController::index($request);

        $requestAlbum = new Request([
            'limit' => 6,
            'order' => 'desc',
            'column' => 'id'
        ]);
        $albums = AlbumController::index($requestAlbum);
        $models = ModelController::index($requestAlbum);
        
        return response()->json([
            "videosBeingWatchedNow" => $videosBeingWatchedNow,
            "videosNew" => $videosNew->original["data"],
            "albums" => $albums->original["data"],
            "models" => $models->original->toArray()["data"]
        ]);
    }

    public function header(Request $request){
        $urlPath = $request->fullUrl();

        $settingsController = new SettingsController;
        $settings = $settingsController->indexFrontend()->original;

        $patterns = [
            '/^\/video\/([\w-]+)$/' => 'video',
            '/^\/gallery\/([\w-]+)$/' => 'album',
            '/^\/videos\/tag\/([\w-]+)$/' => 'tag',
            '/^\/videos\/([\w-]+)$/' => 'category',
            '/^\/channel\/([\w-]+)$/' => 'channel',
            '/^\/model\/([\w-]+)$/' => 'model',
            '/^\/channel\/([\w-]+)$/' => 'channel',
        ];
        
        foreach ($patterns as $pattern => $type) {
            if (preg_match($pattern, $urlPath, $matches)) {
                $id = $matches[1];
                switch($type){
                    case "video":
                        $videoModel = new Video;
                        $video = $videoModel->where("title_slug", $id)->first();
                        if($video){
                            $title = $video->name;    
                        }
                        else {
                            $title = "Not found";
                        }
                        break;
                    case "album":
                        $albumModel = new Album;
                        $album = $albumModel->where("title_slug", $id)->first();
                        if($album){
                            $title = $album->name;    
                        }
                        else {
                            $title = "Not found";
                        }
                        break;
                    default:
                        $title = "";
                }
                return response()->json([
                    'title' => $title." - ".$settings["siteTitle"],
                ]);
            }
        }

        return response()->json([
            'title' => $settings["siteTitle"]
        ]);
    }
}
