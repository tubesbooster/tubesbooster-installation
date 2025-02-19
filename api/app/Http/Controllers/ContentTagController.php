<?php

namespace App\Http\Controllers;

use App\Models\ContentTag;
use Illuminate\Http\Request;

class ContentTagController extends Controller
{
    public static function store(Request $request)
    {   
        $data = UtilsController::convertNullStringsToNull($request->all());
        $name = strtolower($request->name);
        if(!isset($request->slug)){
            $title_slug = UtilsController::createSlug(
                $request->name,
                "content_categories",
                "title_slug",
                $request->id ? $request->id : 0
            );
        }
        else {
            $title_slug = $request->slug;
        }
        
        if($request->id){
            $contentTag = ContentTag::find($request->id);
            $contentTag->update($request->all());
            $contentTag->update(
                array_merge(
                    $data,
                    [
                        "name" => $name,
                        "title_slug" => $title_slug
                    ]
                )
            );
        }
        else {
            $checkExisting = ContentTag::where('name', 'LIKE', $request->name)->whereRaw('LOWER(name) = ?', [strtolower($request->name)])->first();
            if(!$checkExisting){
                $contentTag = new ContentTag(
                    array_merge(
                        $data,
                        [
                            "name" => $name,
                            "title_slug" => $title_slug
                        ]
                    )
                );
                $contentTag->save();   
            }
            else {
                return response()->json([
                    'error_message' => "Tag \"$request->name\" already exists!",
                    'id' => $checkExisting->id
                ]);
            }
        }
        return response()->json(['data' => $contentTag]);
    }

    public function index(Request $request)
    {
        $contentTag = ContentTag::where('name', '!=', "")->where("status", "!=", 2);
        if($request["id"]){
            $contentTag->where('id', $request->id);
        }
        if($request["name"]){
            $contentTag->where('name', 'LIKE', '%'.$request->name.'%');
        }
        if($request["letter"] && $request["letter"] !== "All"){
            $contentTag->where('name', 'like', $request["letter"]."%");
        }
        if($request["letter"]){
            $contentTag->orderBy('name', 'asc');
        }
        if($request["search"] && $request["search"] !== ""){
            $contentTag->where('name', 'like', "%".$request["search"]."%");
        }
        
        $contentTag = $contentTag->paginate($request["page"] ? 50 : 10);
        foreach($contentTag as $key => $tag){
            $contentTag[$key]["total"] = $tag->videos()->where('status', 1)->count();
        }
        return response()->json($contentTag);
    }

    public function indexAdmin(Request $request)
    {
        $contentTag = ContentTag::withCount('videos')->where('name', '!=', "");
        if($request["name"]){
            $contentTag->where('name', 'LIKE', '%'.$request->name.'%');
        }

        //Load default if undefined properly
        $request->order = $request->order !== "desc" ? "asc" : "desc";
        $request->limit = $request->limit === "undefined" ? 10 : (int)$request->limit;
        $request->column = $request->column === "undefined" || empty($request->column) ? "id" : $request->column;

        $contentTag = $contentTag->orderBy($request->column, $request->order);

        $contentTag = $contentTag->paginate($request["limit"] ? (int)$request["limit"] : 10);
        foreach($contentTag as $key => $tag){
            $videos = $tag->videos()->count();
            $albums = $tag->albums()->count();
            $channels = $tag->channels()->count();
            $contentTag[$key] = [
                'id' => $tag["id"],
                'name' => $tag["name"],
                'title_slug' => "/".$tag["title_slug"],
                'status' => str_replace([1,2], ["Online", "Offline"], $tag["status"]),
                'videos' => $videos,
                'albums' => $albums,
                'channels' => $channels,
                'created_at' => date("m/d/Y H:i", strtotime($tag->created_at)),
                'seo_title' => $tag["seo_title"],
                'seo_description' => $tag["seo_description"],
                'seo_keywords' => $tag["seo_keywords"],
            ];
        }
        return response()->json($contentTag);
    }

    public function show($id)
    {
        if($id !== "new"){
            if(ctype_digit($id)){
                $contentTag = ContentTag::find($id);
            }
            else {
                $contentTag = ContentTag::where('title_slug', $id)->first();
            }

            if (!$contentTag) {
                abort(404);
            }

            $videos = $contentTag->videos()->count();
            $albums = $contentTag->albums()->count();
            $channels = $contentTag->channels()->count();

            $contentTag["videos"] = $videos;
            $contentTag["albums"] = $albums;
            $contentTag["channels"] = $channels;
            $contentTag["created"] = date("m/d/Y H:i", strtotime($contentTag->created_at));

            return response()->json($contentTag);
        }
    }

    public function delete(Request $request)
    {
        $ids = explode(",", $request->items);
        foreach($ids as $item){
            $contentTag = ContentTag::find($item);
            $contentTag->delete();
        }
        return response()->json($contentTag);
    }

    public function deleteAll()
    {
        $contentTag = ContentTag::query()->delete();
        return "done";
    }

    public function search(Request $request)
    {
        $contentTag = ContentTag::select("id", "name");
        if($request["selected"]){
            $ids = explode(",", $request["selected"]);
            foreach ($ids as $key => $id) {
                $contentTag->where('id', "!=", $id);
            }
        }
        if($request["q"]){
            $contentTag->where('name', 'LIKE', '%'.$request->q.'%');
        }
        $contentTag = $contentTag->orderBy("name", "asc")->limit(10)->get()->toArray(); 
        return response()->json($contentTag);
    }

    public static function getTagsId($tags){
        foreach ($tags as &$tag) {
            if (substr($tag, 0, 1) === "0") {
                $checkExisting = ContentTag::where('name', 'LIKE', substr($tag, 1))->whereRaw('LOWER(name) = ?', [strtolower(substr($tag, 1))])->first();
                if($checkExisting){
                    $tag = $checkExisting->id;
                }
                else {
                    $newTag = new ContentTag([
                        "name" => substr($tag, 1),
                        "title_slug" => UtilsController::createSlug(substr($tag, 1), "content_tags", "title_slug"),
                        "status" => 1
                    ]);
                    $newTag->save();   
                    $tag = $newTag->id;
                }
            }
        }
        return array_unique($tags);
    }
}
