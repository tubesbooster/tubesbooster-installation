<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\AlbumLike;
use App\Models\ContentCategory;
use App\Models\ContentTag;
use App\Http\Controllers\ContentTagController;
use App\Models\User;
use App\Models\Photo;
use App\Models\AlbumView;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    public static function index(Request $request)
    {
        $albums = Album::query();
        if($request["name"]){
            $albums->where('name', 'LIKE', '%'.$request->name.'%');
        }
        $categories = json_decode($request["categories"]);
        if($request["categories"]){
            if(!empty($categories)){
                foreach ($categories as $category) {
                    $id = $category->id;
                    $albums->orWhereHas('categories', function ($subquery) use ($id) {
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
                $albums->orWhereIn('user_id', $usersArray);
            }
        }
        if($request["model_id"]){
            $modelId = $request["model_id"];
            $albums->whereHas('models', function ($query) use ($modelId) {
                $query->where('models.id', $modelId);
            });
        }
        if($request["channel_id"]){
            $channelId = $request["channel_id"];
            $albums->whereHas('channels', function ($query) use ($channelId) {
                $query->where('channels.id', $channelId);
            });
        }
        if($request["user_id"]){
            $albums->orWhere('user_id', $request["user_id"]);
            if($request["user_id"] == 1){
                $albums->orWhereNull('user_id')->orWhere('user_id', 0);
            }
        }
        if($request["photos"] && $request["photos"] !== "[0,999]"){
            $photos = json_decode($request->photos);
            $minPhotos = $photos[0] ? $photos[0] : 0;
            $maxPhotos = $photos[1];
            $albumIds = Album::select('albums.id')
                ->join('album_photo', 'albums.id', '=', 'album_photo.album_id')
                ->groupBy('albums.id')
                ->havingRaw('COUNT(album_photo.photo_id) BETWEEN ? AND ?', [$minPhotos, $maxPhotos])
                ->pluck('id');
        
            $albums->whereIn('id', $albumIds);
        }
        if($request["status"]){
            $albums->where('status', $request["status"]);
        }
        if($request["type"]){
            $albums->where('type', $request["type"]);
        }
        if($request["date_from"] && $request["date_from"] !== "null" ){
            $date = str_replace('"', '', explode("T", $request["date_from"])[0]);
            $albums->where('created_at', '>=', $date);
        }
        if($request["date_to"] && $request["date_to"] !== "null"){
            $date = str_replace('"', '', explode("T", $request["date_to"])[0]);
            $albums->where('created_at', '<=', $date);
        }
        if($request->header('Show-All') != true){
            $albums->whereHas('categories');
            $albums->where('status', 1);
            $albums->where(function ($query) {
                $query->where('date_scheduled', '<', Carbon::now()->addHours(4)->toDateTimeString())
                      ->orWhereNull('date_scheduled');
            });
        }

        //Load default if undefined properly
        $order = $request->order !== "desc" ? "asc" : "desc";
        $limit = $request->limit === "undefined" ? 10 : (int)$request->limit;
        $column = $request->column === "undefined" || empty($request->column) ? "id" : $request->column;
        
        switch($column){
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
                $orderColumn = $column;
                break;
        }
        if($orderColumn === "photos"){
            $collection = $albums->withCount('photos')->orderBy('photos_count', $order);
        }
        else {
            $collection = $albums->orderBy($orderColumn, $order); 
        }
        $collection = $albums->paginate($limit); 
        foreach($collection as $key => $album){
            $user = User::find($album->user_id);
            if(!isset($user)){
                $user = User::find(1);
            }
            if($album->featured > 0){
                $featured = Photo::find($album->featured);
                $featured = $featured->slug;
            }
            else {
                $featured = $album->photos()->pluck('slug')->first();
            }
            switch((int)$album->status){
                case 2: 
                    $status = "Offline";
                    break;
                case 1: 
                default:
                    $status = "Online";
            }
            switch((int)$album->type){
                case 2: 
                    $type = "Private";
                    break;
                case 3: 
                    $type = "Premium";
                    break;
                case 1: 
                default:
                    $type = "Public";
            }
            
            $models = $album->models()->get()->toArray();
            foreach($models as &$model){
                $model = $model["stage_name"];
            }
            $models = implode(", ", $models);
            $collection[$key] = [
                'id' => $album->id,
                'thumb' => "photos/".$featured.".jpg?t=".time(),
                'name' => $album->name,
                'title' => $album->name,
                'user' => $user ? $user->username : "",
                'userAvatar' => $user ? "avatars/".$user->avatar : null,
                'model' => $models,
                'photos' => $album->photos()->count(),
                'status' => $status,
                'type' => $type,
                'views' => $album->views()->count(),
                'comments' => "0",
                'likes' => "0",
                'created_at' => date("m/d/Y", strtotime($album->created_at)),
                'date_scheduled' => $album->date_scheduled,
                'title_slug' => $album->title_slug
            ];
        }

        $albumIdWithMostPhotos = Album::withCount('photos')
            ->orderByDesc('photos_count')
            ->value('id');
        $numberOfPhotosInMostAlbum = $albumIdWithMostPhotos ? Album::find($albumIdWithMostPhotos)->photos()->count() : 0;

        return response()->json(array_merge($collection->toArray(), [
            "meta" => [
                "maxPhotos" => $numberOfPhotosInMostAlbum
            ]
        ]));
    }
    
    public function store(Request $request)
    {
        if($request->date_scheduled){
            if($request->date_scheduled == "null"){
                $request["date_scheduled"] = date('Y-m-d H:i');
            }
            else {
                $date = new \DateTime($request->date_scheduled);
                $formattedDate = $date->format('Y-m-d H:i');
                $request["date_scheduled"] = $formattedDate;
            }
        }
        if($request->id){
            $album = Album::find($request->id);
            $album->categories()->detach();
            $album->tags()->detach();
            $album->channels()->detach();
            $album->models()->detach();
            if($request->categories && count($request->categories) > 0){
                $categoriesIds = ContentCategoryController::getCategoriesId($request->categories);
                $album->categories()->attach($categoriesIds);
            }
            if($request->tags && count($request->tags) > 0){
                $tagIds = ContentTagController::getTagsId($request->tags);
                $album->tags()->attach($tagIds);
            }
            if($request->channels && count($request->channels) > 0){
                $album->channels()->attach($request->channels);
            }
            if($request->models && count($request->models) > 0){
                $album->models()->attach($request->models);
            }
            if(gettype($request->featured) === "object"){
                $photo = $this->createPhoto();
                $request->featured->move(public_path("photos") ,$photo->slug.".jpg");
                $albumData["featured"] = $photo->id;
            }
            elseif(gettype($request->featured) === "string") {
                $albumData["featured"] = false;
            }
            else {
                $albumData["featured"] = null;
            }
            if($albumData["featured"] === false){
                $albumData["featured"] = $album->featured;
            }
            
            $photosDebug = [];
            if($request->photosToUpload){
                foreach($request->photosToUpload as $file){
                    $photo = $this->createPhoto();
                    $photosDebug[] = $photo;
                    $file->move(public_path("photos") ,$photo->slug.".jpg");
                    $album->photos()->attach($photo->id);
                }
            }
            //dd($photosDebug);

            $albumData["name"] = $request->name;
            $albumData["description"] = $request->description;
            $albumData["status"] = $request->status;
            $albumData["type"] = $request->type;
            $albumData["date_scheduled"] = $request->date_scheduled;
            $albumData["user_id"] = $request->user_id;
            $albumData["title_slug"] = UtilsController::createSlug($request->name, "albums", "title_slug", $request->id);
            $album->update($albumData);
        }
        else {
            $album = new Album($request->only(["name"]));
            $album->title_slug = UtilsController::createSlug($request->name, "albums", "title_slug");
            $album->save();   
            if($request->categories && count($request->categories) > 0){
                $categoriesIds = ContentCategoryController::getCategoriesId($request->categories);
                $album->categories()->attach($categoriesIds);
            }
            if($request->tags && count($request->tags) > 0){
                $tagIds = ContentTagController::getTagsId($request->tags);
                $album->tags()->attach($tagIds);
            }
            if($request->channels && count($request->channels) > 0){
                $album->channels()->attach($request->channels);
            }
            if($request->models && count($request->models) > 0){
                $album->models()->attach($request->models);
            }
            if(gettype($request->featured) === "object"){
                $photo = $this->createPhoto();
                $request->featured->move(public_path("photos") ,$photo->slug.".jpg");
                $albumData["featured"] = $photo->id;
            }
            elseif(gettype($request->featured) === "string") {
                $albumData["featured"] = false;
            }
            else {
                $albumData["featured"] = null;
            }
            if($request->photosToUpload){
                foreach($request->photosToUpload as $file){
                    $photo = $this->createPhoto();
                    $file->move(public_path("photos") ,$photo->slug.".jpg");
                    $album->photos()->attach($photo->id);
                }
            }
            $id = hash("crc32", $album->id);
            $slug = str_slug("$id-$album->name", "-");
            $albumData["description"] = $request->description;
            $albumData["status"] = $request->status;
            $albumData["type"] = $request->type;
            $albumData["date_scheduled"] = $request->date_scheduled;
            $albumData["slug"] = $slug;
            $albumData["user_id"] = $request->user_id;
            $album->update($albumData);
        }
        return response()->json(['data' => $album]);
    }

    public function show($id, Request $request)
    {
        $album = array();
        $users = User::get()->toArray();
        foreach($users as $userId => $user){
            $users[$userId]["name"] = $user["username"];
        }
        $meta = [
            "user_id" => $users
        ];
        if($id !== "new" && $id !== 0){
            if(ctype_digit($id)){
                $album = Album::where('id', $id);
            }
            else {
                $album = Album::where('title_slug', $id);
            }
            if($request->header('Show-All') != true){
                $album->where('status', 1);
            }
            $album = $album->first();

            if (!$album) {
                abort(404);
            }
            // Get client IP address
            $ip = $_SERVER['REMOTE_ADDR'];
            // Check if a record with the same video_id and ip exists
            $existingView = AlbumView::where('album_id', $album->id)->where('ip', $ip)->first();
            // If no existing record found, create a new one
            if (!$existingView) {
                // Create a new entry in the video_views table
                $newView = new AlbumView();
                $newView->album_id = $album->id;
                $newView->ip = $ip;
                $newView->save();
            }
            $photos = array();
            foreach($album->photos()->pluck('slug')->toArray() as $photo){
                $photos[] = asset('photos/'.$photo.'.jpg');
            }
            $user = User::find($album->user_id);
            if(!isset($user)){
                $user = User::find(1);
            }
            $username = $user->display_name;
            $words = str_word_count($username, 1);
            $userInitials = array_map(function($word) {
                return substr($word, 0, 1);
            }, $words);
            $userInitials = implode('', $userInitials);
            if($album->featured > 0){
                $featured = Photo::find($album->featured);
                $featured = asset("photos/".$featured->slug.".jpg");
            }
            else {
                $featured = "";
            }
            $models = $album
                ->models()
                ->select(
                    'models.id',
                    'models.name',
                    'models.stage_name',
                    'models.thumbnail',
                    'models.title_slug'
                )
                ->get();
            foreach ($models as $key => $entry) {
                $models[$key] = [
                    'id' => $entry->id,
                    'name' => $entry->stage_name,
                    'title_slug' => $entry->title_slug,
                    'avatar' => asset("avatars/$entry->thumbnail")
                ];
            }

            // Get information about current client like
            $albumLike = $album->likes()->select("type")->where("album_likes.ip_address", $request->ip())->first();
            if($albumLike){
                $albumLike->isLiked = true;
            }
            else {
                $albumLike = [
                    "isLiked" => false,
                    "type" => 0
                ];
            }

            if($request->header('Show-All') != true) {
                $categories = $album->categories()->select('id', 'name', 'title_slug')->where("status", '!=', 2)->get();
                $tags = $album->tags()->select('id', 'name', 'title_slug')->where('status', '!=', 2)->get();
            }
            else {
                $categories = $album->categories()->select('id', 'name', 'title_slug')->get();
                $tags = $album->tags()->select('id', 'name', 'title_slug')->get();
            }

            return response()->json([
                'id' => $album->id,
                'name' => $album->name,
                'source' => $album->source,
                'url' => $album->url,
                'description' => $album->description == "null" ? "" : $album->description,
                'categories' => $categories,
                'tags' => $tags,
                'channels' => $album->channels()->select('channels.id', 'channels.title as name')->get(),
                'models' => $models,
                'meta' => $meta,
                'photos' => $photos,
                'photosToUpload' => [],
                'user' => $username,
                'status' => (int)$album->status,
                'type' => (int)$album->type,
                'userAvatar' => $user ? "avatars/".$user->avatar : null,
                'userInitials' => $userInitials,
                'userCreatedAt' => $user ? date("m/d/Y", strtotime($user->created_at)) : "-",
                'user_id' => $user->id,
                'featured' => "",
                'current_featured' => $featured,
                'views' => $album->views()->count(),
                'date_scheduled' => $album->date_scheduled,
                'channel' => $album->channels()->first(),
                'likes' => $album->likes()->where("type", 0)->count(),
                'dislikes' => $album->likes()->where("type", 1)->count(),
                'clientLike' => $albumLike,
            ]);
        }
        return response()->json([
            'meta' => $meta
        ]);
    }

    public function delete(Request $request)
    {
        $ids = explode(",", $request->items);
        foreach($ids as $item){
            $album = Album::find($item);
            $album->delete();
        }
        return response()->json($album);
    }

    public function deletePhoto($slug)
    {
        $photo = Photo::where(["slug" => $slug])->first();
        $photo->delete();
        return response()->json($photo);
    }

    public function createPhoto(){
        $photo = new Photo(["slug" => "temp"]);
        $photo->save();
        $slug = md5('photohash'.$photo->id);
        $photo->update(["slug" => $slug]);
        return $photo;
    }

    public function bulkEdit(Request $request)
    {
        $albums = explode(",", $request->selected);
        foreach($albums as $albumId){
            $album = Album::find($albumId);
            if($request->categories && count($request->categories) > 0){
                $album->categories()->syncWithoutDetaching($request->categories);
            }
            if($request->tags && count($request->tags) > 0){
                $album->tags()->syncWithoutDetaching($request->tags);
            }
            if($request->models && count($request->models) > 0){
                $album->models()->syncWithoutDetaching($request->models);
            }
            if($request->channels && count($request->channels) > 0){
                $album->channels()->syncWithoutDetaching($request->channels);
            }
            $requestData = $request->only(["status", "type", "date_scheduled"]);
            $requestData["status"] = $requestData["status"] == 0 ? $album->status : $requestData["status"];
            $requestData["type"] = $requestData["type"] == 0 ? $album->type : $requestData["type"];
            if($requestData["date_scheduled"] == "null"){
                $requestData["date_scheduled"] = $album->date_scheduled;
            }
            $album->update($requestData);      
        }
    }

    public function like(Request $request){
        $albumLike = AlbumLike::where("ip_address", $request->ip())
            ->where("album_id", $request->id)
            ->first();
        if($albumLike){
            if($albumLike->type === (int)$request->type){
                $albumLike->forceDelete();    
            }
            else {
                $albumLike->update([
                    "type" => (int)$request->type
                ]);
            }
        }
        else {
            $albumLike = AlbumLike::create([
                "album_id" => $request->id,
                "ip_address" => $request->ip(),
                "type" => $request->type
            ]);
            $albumLike->save();
        } 
    }
}
