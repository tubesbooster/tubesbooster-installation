<?php

namespace App\Http\Controllers;

use App\Models\SiteModel as Model;
use App\Models\Video;
use App\Models\Country;
use App\Models\Language;
use App\Http\Controllers\UtilsController;
use Illuminate\Http\Request;

class ModelController extends Controller
{
    public function store(Request $request)
    {
        $title_slug = UtilsController::createSlug(
            isset($request->stage_name) ? $request->stage_name : $request->name,
            "models",
            "title_slug",
            $request->id > 0 ? $request->id : 0
        );

        if(gettype($request->thumbnail) === "object"){
            $ext = explode(".", $request->thumbnail->getClientOriginalName());
            $filename = 'model-'.$request->name.'-'.md5(time()).'.'.end($ext);
            $request->thumbnail->move(public_path("avatars") ,$filename);
            $request->thumbnail = $filename;
        }
        elseif(gettype($request->thumbnail) === "string") {
            $request->thumbnail = false;
        }
        else {
            $request->thumbnail = " ";
        }
        if(gettype($request->cover) === "object"){
            $ext = explode(".", $request->cover->getClientOriginalName());
            $filename = 'model-'.$request->name.'-'.md5(time()).'.'.end($ext);
            $request->cover->move(public_path("model-covers") ,$filename);
            $request->cover = $filename;
        }
        elseif(gettype($request->cover) === "string") {
            $request->cover = false;
        }
        else {
            $request->cover = " ";
        }
        if($request->country){
            $country = (int)$request->country[0];
        }
        else {
            $country = "";
        }
        if($request->languages){
            $languages = json_encode($request->languages);
        }
        else {
            $languages = "";
        }
        $social_media = json_encode([
            "onlyfans" => $request->onlyfans,
            "twitter" => $request->twitter,
            "reddit" => $request->reddit,
            "amazon" => $request->amazon,
            "instagram" => $request->instagram,
            "facebook" => $request->facebook,
        ]);
        $data = $request->all();
        $data = UtilsController::convertNullStringsToNull($data);
        
        if($request->id){
            $model = Model::find($request->id);
            if($request->thumbnail === false){
                $request->thumbnail = $model->thumbnail;
            }
            if($request->cover === false){
                $request->cover = $model->cover;
            }
            $model->update(array_merge($data, [ 
                "thumbnail" => $request->thumbnail, 
                "cover" => $request->cover, 
                "country" => $country, 
                "languages" => $languages , 
                "social_media" => $social_media,
                "title_slug" => $title_slug
            ]));
        }
        else {
            $model = new Model(array_merge($data, [ 
                "thumbnail" => $request->thumbnail, 
                "cover" => $request->cover, 
                "country" => $country, 
                "languages" => $languages, 
                "social_media" => $social_media,
                "title_slug" => $title_slug
            ]));
            $model->save();   
        }
        return response()->json(['data' => $model]);
    }

    public static function index(Request $request)
    {
        $collection = Model::query();
        if($request["name"]){
            $collection->where(function($query) use ($request){
                $query->where('name', 'LIKE', '%'.$request->name.'%')
                    ->orWhere('stage_name', 'LIKE', '%'.$request->name.'%');
            });
        }
        if($request["channel_id"]){
            $channelId = $request["channel_id"];
            $collection->whereHas('channels', function ($query) use ($channelId) {
                $query->where('channels.id', $channelId);
            });
        }

        if($request->header('Show-All') != true){
            $collection->where('status', 1);
        }

        if(isset($request->relatedTo)){
            $siteModel = Model::findOrFail($request->relatedTo);
            $videoIds = $siteModel->videos()->pluck('videos.id');
            $collection->whereHas('videos', function ($query) use ($videoIds) {
                    $query->whereIn('videos.id', $videoIds);
                })
                ->where('id', '<>', $request->relatedTo)
                ->withCount(['videos as common_videos_count' => function ($query) use ($videoIds) {
                    $query->whereIn('videos.id', $videoIds);
                }]);
        }

        //Load default if undefined properly
        $request->order = $request->order !== "desc" ? "asc" : "desc";
        $request->limit = $request->limit === "undefined" || empty($request->limit) ? 10 : (int)$request->limit;
        $request->column = $request->column === "undefined" || empty($request->column) ? "id" : $request->column;
        
        switch($request->column){
            case "registration_date":
                $orderColumn = "id";
                break;
            case "content":
                $orderColumn = "video_count";
                break;
            case "realName":
                $orderColumn = "name";
                break;
            default:
                $orderColumn = $request->column;
                break;
        }
        if($orderColumn == "videos"){
            $collection = $collection->withCount('videos')->orderBy('videos_count', $request->order);
        }
        elseif($orderColumn == "albums"){
            $collection = $collection->withCount('albums')->orderBy('albums_count', $request->order);
        }
        else {
            $collection = $collection->orderBy($orderColumn, $request->order);
        }
        $collection = $collection->paginate($request->limit); 
        foreach($collection as $key => $model){
            $collection[$key] = [
                'id' => $model->id,
                'nameAvatar' => "avatars/".$model->thumbnail,
                'stage_name' => $model->stage_name ? $model->stage_name : $model->name,
                'name' => $model->stage_name ? $model->name || $model->name == "null" ? "" : $model->name : "",
                'realName' => $model->name,
                'videos' => $model->videos()->count(),
                'albums' => $model->albums()->count(),
                'title_slug' => $model->title_slug
            ];
        }
        return response()->json($collection);
    }

    public function show($id, Request $request)
    {
        if($id !== "new"){
            if(ctype_digit($id)){
                $modelModel = Model::with(['videos.views', 'albums.views'])->where('id', $id);
            }
            else {
                $modelModel = Model::with(['videos.views', 'albums.views'])->where('title_slug', $id);
            }
            if($request->header('Show-All') != true){
                $modelModel->where('status', 1);
            }
            $modelModel = $modelModel->first();

            if (!$modelModel) {
                abort(404);
            }

            $videoViews = 0;
            foreach ($modelModel->videos as $video) {
                $videoViews += $video->views()->count() + $video->views;
            }
            $albumViews = 0;
            foreach ($modelModel->albums as $album) {
                $albumViews += $album->views()->count();
            }
            $albumCount = 0;
            $photoCount = 0;
            foreach ($modelModel->albums()->get() as $key => $album) {
                $albumCount++;
                $photoCount = $photoCount + $album->photos()->count();
            }
            $model = $modelModel->toArray();
            $country = Country::find((int)$model["country"]);
            $social_media = json_decode($model["social_media"]);
            foreach ($model as $key => $value) {
                $model[$key] = $value == "null" ? "" : $value;
            }
            $model["country"] = $country ? [$country] : [];
            $model["name"] = $model["name"] != "null" ? $model["name"] : "";
            $model["stage_name"] = $model["stage_name"] != "null" ? $model["stage_name"] : "";
            $model["name"] = empty($model["name"]) ? $model["stage_name"] : $model["name"];
            $model["city"] = $model["city"] != "null" ? $model["city"] : "";
            $model["height"] = $model["height"] != "null" ? $model["height"] : "";
            $model["weight"] = $model["weight"] != "null" ? $model["weight"] : "";
            $model["measurements"] = $model["measurements"] != "null" ? $model["measurements"] : "";
            $model["site_name"] = $model["site_name"] != "null" ? $model["site_name"] : "";
            $model["url"] = $model["url"] != "null" ? $model["url"] : "";
            $model["biography"] = $model["biography"] != "null" ? $model["biography"] : "";
            $model["interests"] = $model["interests"] != "null" ? $model["interests"] : "";
            $model["birth_date"] = $model["birth_date"] != "null" ? $model["birth_date"] : null;
            $model["current_thumbnail"] = asset("avatars/".$model["thumbnail"]);
            $model["current_cover"] = asset("model-covers/".$model["cover"]);
            if($social_media){
                foreach ($social_media as $key => $value) {
                    $model[$key] = $value != "null" ? $value : "";
                }
            }
            $model["videos"] = $modelModel->videos()->count();
            $model["albums"] = $albumCount;
            $model["photos"] = $photoCount;
            $model["orientation"] = $model["orientation"] == null ? 0 : $model["orientation"];
            $model["gender"] = $model["gender"] == null ? 0 : $model["gender"];
            $model["hair_color"] = $model["hair_color"] == null ? 0 : $model["hair_color"];
            $model["orientationText"] = str_replace([0,1,2,3,4,5], ["Rather not say", "Straight", "Gay", "Lesbian", "Not sure", "Bisexual"], $model["orientation"]);
            $model["hairColorText"] = str_replace([0,1,2,3,4,5], ["N/A", "Black", "Brown", "Blonde", "Red", "Gray"], $model["hair_color"]);
            $model['videoViews'] = $videoViews;
            $model['albumViews'] = $albumViews;
            $model["relationshipText"] = str_replace([0,1,2,3,4,5], ["Rather not say", "Single", "In a relationship", "Married", "Engaged", "It's complicated", "Divorced", "Widowed"], (int)$model["relationship"]);
            $model["tattoosText"] = str_replace([1,2], ["Yes", "No"], $model["tattoos"]);
            $model["piercingsText"] = str_replace([1,2], ["Yes", "No"], $model["piercings"]);
            $model["careerStatusText"] = str_replace([1,2], ["Active", "Retired"], $model["career_status"]);
            $model["genderText"] = str_replace([0,1,2,3,4], ["N/A", "Male", "Female", "Transexual", "Couple"], $model["gender"]);
            $model["measurementsText"] = $model["measurements"] != "" ? $model["measurements"] : "N/A";
            $model["weightText"] = $model["weight"] != "" ? $model["weight"] : "N/A";
            $model["heightText"] = $model["height"] != "" ? $model["height"] : "N/A";
            $model["eye_colorText"] = str_replace([0,1,2,3,4,5], ["N/A", "Brown", "Blue", "Green", "Gray", "Black"], (int)$model["eye_color"]);
            $model["ethnicityText"] = str_replace([0,1,2,3,4,5,6,7], ["N/A","Asian","Black","Indian","Latino","Middle Eastern","Mixed","White"], (int)$model["ethnicity"]);
            $model["eye_color"] = $model["eye_color"] == null ? "" : $model["eye_color"];
            $model["ethnicity"] = $model["ethnicity"] == null ? "" : $model["ethnicity"];
            $model["interestsText"] = empty($model["interests"]) ? "N/A" : $model["interests"];

            //Get featured videos
            $request = new Request([
                "model_id" => $model["id"],
                "column" => "views",
                "order" => "desc",
                "limit" => 10,
                "disableAds" => true
            ]);
            $model["featuredVideos"] = VideoController::index($request)->original["data"];
            $model["featuredTitle"] = explode(" ", empty($model["stage_name"]) ? $model["name"] : $model["stage_name"])[0]."'s videos";
            
            //Get related models
            $request = new Request([
                "relatedTo" => $model["id"],
                "column" => "common_videos_count",
                "order" => "desc",
                "limit" => 7
            ]);
            $model["relatedModels"] = $this->index($request)->original->toArray();
            $model["relatedModels"] = $model["relatedModels"]["data"];

            return response()->json($model);
        }
    }

    public function delete(Request $request)
    {
        $ids = explode(",", $request->items);
        foreach($ids as $item){
            $model = Model::find($item);
            $model->delete();
        }
        return response()->json($model);
    }

    public function search(Request $request)
    {
        $model = Model::selectRaw("id, TRIM(REPLACE(TRIM(stage_name), '\n', '')) as name");
        if($request["selected"]){
            $ids = explode(",", $request["selected"]);
            foreach ($ids as $key => $id) {
                $model->where('id', "!=", $id);
            }
        }
        if($request["q"]){
            $model->where('stage_name', 'LIKE', '%'.$request->q.'%');
        }
        $collection = $model->orderBy("name", "asc")->limit(10)->get(); 

        return response()->json($collection);
    }
}
