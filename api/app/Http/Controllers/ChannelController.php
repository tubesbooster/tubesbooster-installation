<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Channel;
use App\Models\ChannelView;
use App\Models\ContentCategory;
use App\Models\ContentTag;
use App\Http\Controllers\ContentCategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ChannelController extends Controller
{
    public static function index(Request $request)
    {
        $channels = Channel::withCount("views");
        
        if($request["name"]){
            $channels->where('title', 'LIKE', '%'.$request->name.'%');
        }
        
        if($request["models"]){
            $models=json_decode($request["models"]);
            foreach ($models as $key => $value) {
                $modelId = $value->id;
                $channels->whereHas('models', function ($query) use ($modelId) {
                    $query->where('models.id', $modelId);
                });
            }
        }
        
        if($request["tags"]){
            $tags=json_decode($request["tags"]);
            foreach ($tags as $key => $value) {
                $tagId = $value->id;
                $channels->whereHas('tags', function ($query) use ($tagId) {
                    $query->where('content_tags.id', $tagId);
                });
            }
        }
        
        if($request["categories"]){
            $categories=json_decode($request["categories"]);
            foreach ($categories as $key => $value) {
                $categoryId = $value->id;
                $channels->whereHas('categories', function ($query) use ($categoryId) {
                    $query->where('content_categories.id', $categoryId);
                });
            }
        }

        if($request["status"]){
            $channels->where('status', $request["status"]);
        }

        if($request->header('Show-All') != true){
            $channels->where('status', 1);
        }

        //Load default if undefined properly
        $request->order = $request->order !== "desc" ? "asc" : "desc";
        $request->limit = $request->limit === "undefined" ? 10 : (int)$request->limit;
        $request->column = $request->column === "undefined" || empty($request->column) ? "id" : $request->column;
        
        if($request->column === "views"){
            $request->column = "views_count";
        }
        
        $channels = $channels->orderBy($request->column, $request->order);
        $collection = $channels->paginate($request->limit); 

        foreach($collection as $key => $channel){
            $videoTotal = 0;
            $videoViews = 0;
            foreach ($channel->videos->where("status", 1) as $video) {
                $videoTotal++;
                $videoViews += $video->views()->count() + $video->views;
            }
            $collection[$key] = [
                'logo' => "channel-banners/".$channel->logo,
                'id' => $channel->id,
                'title' => $channel->title,
                'status' => str_replace([1,2], ["Online", "Offline"], $channel->status),
                'short_description' => $channel->short_description,
                'views' => $channel->views()->count(),
                'created' => "Created: ".date("m/d/Y", strtotime($channel->created_at)),
                'cover' => "channel-banners/".$channel->cover,
                'title_slug' => $channel->title_slug,
                'videos' => $videoTotal,
                'videoViews' => $videoViews
            ];
        }

        return response()->json($collection);
    }

    public function store(Request $request)
    {
        $title_slug = UtilsController::createSlug($request->title, "channels", "title_slug", isset($request->id) ? $request->id : 0);

        if(gettype($request->banner_wide) === "object"){
            $ext = explode(".", $request->banner_wide->getClientOriginalName());
            $filename = Str::slug($request->title, '-').'-wide-'.md5(time()).'.'.end($ext);
            $request->banner_wide->move(public_path("channel-banners") ,$filename);
            $request->banner_wide = $filename;
        }
        elseif(gettype($request->cover) === "string") {
            $request->banner_wide = false;
        }
        else {
            $request->banner_wide = " ";
        }
        if(gettype($request->cover) === "object"){
            $ext = explode(".", $request->cover->getClientOriginalName());
            $filename = Str::slug($request->title, '-').'-cover-'.md5(time()).'.'.end($ext);
            $request->cover->move(public_path("channel-banners") ,$filename);
            $request->cover = $filename;
        }
        elseif(gettype($request->cover) === "string") {
            $request->cover = false;
        }
        else {
            $request->cover = " ";
        }
        if(gettype($request->logo) === "object"){
            $ext = explode(".", $request->logo->getClientOriginalName());
            $filename = Str::slug($request->title, '-').'-logo-'.md5(time()).'.'.end($ext);
            $request->logo->move(public_path("channel-banners") ,$filename);
            $request->logo = $filename;
        }
        elseif(gettype($request->logo) === "string") {
            $request->logo = false;
        }
        else {
            $request->logo = " ";
        }
        if(gettype($request->banner_square) === "object"){
            $ext = explode(".", $request->banner_square->getClientOriginalName());
            $filename = Str::slug($request->title, '-').'-square-'.md5(time()).'.'.end($ext);
            $request->banner_square->move(public_path("channel-banners") ,$filename);
            $request->banner_square = $filename;
        }
        elseif(gettype($request->cover) === "string") {
            $request->banner_square = false;
        }
        else {
            $request->banner_square = " ";
        }
        if($request->id){
            $channel = Channel::findOrFail($request->id);
            $channel->categories()->detach();
            $channel->tags()->detach();
            $channel->models()->detach();
            if($request->categoriesNew && count($request->categoriesNew) > 0){
                $categoriesIds = ContentCategoryController::getCategoriesId($request->categoriesNew);
                $channel->categories()->attach($categoriesIds);
            }
            if($request->tagsNew && count($request->tagsNew) > 0){
                $tagIds = ContentTagController::getTagsId($request->tagsNew);
                $channel->tags()->attach($tagIds);
            }
            if($request->models && count($request->models) > 0){
                foreach ($request->models as $model) {
                    $channel->models()->attach($model);
                }
            }
            if(!$request->banner_wide){
                $request->banner_wide = $channel->banner_wide;
            }
            if(!$request->banner_square){
                $request->banner_square = $channel->banner_square;
            }
            if(!$request->cover){
                $request->cover = $channel->cover;
            }
            if(!$request->logo){
                $request->logo = $channel->logo;
            }
            $channel->update(
                array_merge(
                    $request->all(),
                    [
                        "banner_wide" => $request->banner_wide,
                        "banner_square" => $request->banner_square,
                        "cover" => $request->cover,
                        "logo" => $request->logo,
                        "title_slug" => $title_slug
                    ]
                )
            );
        }
        else {
            $checkExisting = Channel::where('title', 'LIKE', $request->title)->whereRaw('LOWER(title) = ?', [strtolower($request->title)])->first();
            if(!$checkExisting){
                $channel = new Channel(array_merge($request->all(), [ "banner_wide" => $request->banner_wide, "banner_square" => $request->banner_square, "cover" => $request->cover, "logo" => $request->logo ]));
                $channel->title_slug = $title_slug;
                $channel->save();
                if($request->categoriesNew && count($request->categoriesNew) > 0){
                    foreach ($request->categoriesNew as $category) {
                        if (substr($category, 0, 1) === "0") {
                            $newCategory = new ContentCategory(["name" => substr($category, 1)]);
                            $newCategory->save();   
                            $categoryId = $newCategory->id;
                        } else {
                            $categoryId = $category;
                        }
                        $channel->categories()->attach($categoryId);
                    }
                }
                if($request->tagsNew && count($request->tagsNew) > 0){
                    $tagIds = ContentTagController::getTagsId($request->tagsNew);
                    $channel->tags()->attach($tagIds);
                }  
                if($request->models && count($request->models) > 0){
                    foreach ($request->models as $model) {
                        $channel->models()->attach($model);
                    }
                } 
            }
            else {
                return response()->json([
                    "error_message" => "Channel \"$request->title\" already exists!"
                ]);
            }
        }

        return response()->json($channel, 201);
    }

    public function show($id, Request $request)
    {
        if(ctype_digit($id)){
            $channel = Channel::where('id', $id);
        }
        else {
            $channel = Channel::where('title_slug', $id);
        }
        if($request->header('Show-All') != true){
            $channel->where('status', 1);
        }
        $channel = $channel->first();

        if (!$channel) {
            abort(404);
        }

        // Get client IP address
        $ip = $_SERVER['REMOTE_ADDR'];
        // Check if a record with the same video_id and ip exists
        $existingView = $channel->views()->where('ip', $ip)->first();
        // If no existing record found, create a new one
        if (!$existingView) {
            // Create a new entry in the video_views table
            $newView = new ChannelView();
            $newView->channel_id = $channel->id;
            $newView->ip = $ip;
            $newView->save();
        }

        $videoTotal = 0;
        $videoViews = 0;
        if($channel){
            foreach ($channel->videos->where("status", 1) as $video) {
                $videoTotal++;
                $videoViews += $video->views()->count() + $video->views;
            }
            $albumViews = 0;
            $albumTotal = 0;
            foreach ($channel->albums->where("status", 1) as $album) {
                $albumTotal++;
                $albumViews += $album->views()->count();
            }
        }
        $views = $channel->views()->count();

        if($request->header('Show-All') != true) {
            $categories = $channel->categories()->select('content_categories.id', 'content_categories.name', 'content_categories.title_slug')->where("status", '!=', 2)->get();
            $tags = $channel->tags()->select('content_tags.id', 'content_tags.name', 'content_tags.title_slug')->where('status', '!=', 2)->get();
        }
        else {
            $categories = $channel->categories()->select('content_categories.id', 'content_categories.name', 'content_categories.title_slug')->get();
            $tags = $channel->tags()->select('content_tags.id', 'content_tags.name', 'content_tags.title_slug')->get();
        }

        $models = $channel->models()->select('models.id', 'models.name', 'models.stage_name', 'models.thumbnail')->get();
        foreach ($models as $key => $entry) {
            $models[$key] = [
                'id' => $entry->id,
                'name' => $entry->stage_name,
                'title_slug' => $entry->title_slug,
                'thumbnail' => asset("avatars/$entry->thumbnail")
            ];
        }

        // Featured videos
        $featuredRequest = new Request([
            "order" => "desc",
            "channel_id" => $channel->id,
            "column" => "views",
            "limit" => 5,
            "disableAds" => true
        ]);
        $featured = array();
        $featured = VideoController::index($featuredRequest)->original["data"];

        $channel = $channel->toArray();
        $channel["current_banner_square"] = asset("channel-banners/".$channel["banner_square"]);
        $channel["current_banner_wide"] = asset("channel-banners/".$channel["banner_wide"]);
        $channel["current_cover"] = asset("channel-banners/".$channel["cover"]);
        $channel["current_logo"] = asset("channel-banners/".$channel["logo"]);
        $channel["categoriesNew"] = $categories;
        $channel["tagsNew"] = $tags;
        $channel["models"] = $models;
        $channel["status"] = (int)$channel["status"];
        $channel["description"] = $channel["description"] ? $channel["description"] : "";
        $channel["short_description"] = $channel["short_description"] ? $channel["short_description"] : "";
        $channel["featured"] = $featured;
        $channel["referral_link"] = $channel["referral_link"] ? $channel["referral_link"] : "";
        $channel["referral_label"] = $channel["referral_label"] ? $channel["referral_label"] : "";
        $channel["show_user"] = $channel["show_user"] ? (int)$channel["show_user"] : 1;
        $channel["videos"] = $videoTotal;
        $channel["albums"] = $albumTotal;
        $channel['videoViews'] = $videoViews;
        $channel['albumViews'] = $albumViews;
        $channel['views'] = $views;
        return response()->json($channel);
    }

    public function destroy(Request $request)
    {
        $ids = explode(",", $request->items);
        foreach($ids as $item){
            $grabber = Channel::find($item);
            $grabber->delete();
        }
        return response()->json($grabber);
    }

    public function search(Request $request)
    {
        $channel = Channel::selectRaw("id, TRIM(REPLACE(TRIM(title), '\n', '')) as name");
        if($request["selected"]){
            $ids = explode(",", $request["selected"]);
            foreach ($ids as $key => $id) {
                $channel->where('id', "!=", $id);
            }
        }
        if($request["q"]){
            $channel->having('name', 'LIKE', '%'.$request->q.'%');
        }
        $channel = $channel->orderBy("name", "asc")->limit(10)->get()->toArray(); 
        return response()->json($channel);
    }
}