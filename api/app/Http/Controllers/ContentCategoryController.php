<?php

namespace App\Http\Controllers;

use App\Models\ContentCategory;
use App\Models\ContentTag;
use Illuminate\Http\Request;

class ContentCategoryController extends Controller
{
    public function store(Request $request)
    {
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
        if(gettype($request->thumbnail) === "object"){
            $ext = explode(".", $request->thumbnail->getClientOriginalName());
            $filename = md5(time()).'.'.end($ext);
            $request->thumbnail->move(public_path("category-thumbnails") ,$filename);
            $request->thumbnail = $filename;
        }
        elseif(gettype($request->thumbnail) === "string") {
            $request->thumbnail = false;
        }
        else {
            $request->thumbnail = " ";
        }
        if($request->id){
            $contentCategory = ContentCategory::find($request->id);
            $contentCategory->tags()->detach();
            if($request->tags && count($request->tags) > 0){
                $tagIds = ContentTagController::getTagsId($request->tags);
                $contentCategory->tags()->attach($tagIds);
            }
            if($request->thumbnail === false){
                $request->thumbnail = $contentCategory->thumbnail;
            }
            $contentCategory->update(
                array_merge(
                    $request->all(),
                    [
                        "thumbnail" => $request->thumbnail,
                        "title_slug" => $title_slug
                    ]
                )
            );
        }
        else {
            $contentCategory = [];
            $checkExisting = ContentCategory::where('name', 'LIKE', $request->name)->whereRaw('LOWER(name) = ?', [strtolower($request->name)])->first();
            if(!$checkExisting){
                $contentCategory = new ContentCategory(
                    array_merge(
                        $request->all(),
                        [
                            "thumbnail" => $request->thumbnail,
                            "title_slug" => $title_slug
                        ]
                    )
                );
                $contentCategory->save();
                if($request->tags && count($request->tags) > 0){
                    foreach ($request->tags as $tag) {
                        if (substr($tag, 0, 1) === "0") {
                            $newTag = new ContentTag(["name" => substr($tag, 1)]);
                            $newTag->save();   
                            $tagId = $newTag->id;
                        } else {
                            $tagId = $tag;
                        }
                        $contentCategory->tags()->attach($tagId);
                    }
                }
            }
            else {
                return response()->json([
                    'error_message' => "Category \"$request->name\" already exists!",
                    'id' => $checkExisting->id
                ]);
            }
        }
        return response()->json(['data' => $contentCategory]);
    }

    public function index(Request $request)
    {
        $contentCategory = ContentCategory::where( 
            function ($query) {
                $query->where("parent_id", 0)
                    ->orWhereNull("parent_id");
            }
        );
        
        if($request->header('Show-All') != true){
            $contentCategory->where('status', 1);
        }

        if($request["status"]){
            $contentCategory->where('status', $request["status"]);
        }

        $contentCategory = $contentCategory->get()->toArray();
        $result = [];
        foreach ($contentCategory as &$category) {
            $category["name"] = ucwords($category["name"]);
            $category["created_at"] = date("m/d/Y", strtotime($category["created_at"]));
            $result[] = $category;
            $children = ContentCategory::where('parent_id', $category["id"])->get()->toArray();
            foreach($children as &$child){
                $child["created_at"] = date("m/d/Y", strtotime($child["created_at"]));
                $result[] = $child;
            }
        } 
        return response()->json($result);
    }

    public function indexAdmin(Request $request)
    {
        $contentCategory = ContentCategory::where('name', '!=', "");
        
        if($request["name"]){
            $contentCategory->where('name', 'LIKE', '%'.$request->name.'%');
        }

        //Load default if undefined properly
        $request->order = $request->order !== "desc" ? "asc" : "desc";
        $request->limit = $request->limit === "undefined" ? 10 : (int)$request->limit;
        $request->column = $request->column === "undefined" || empty($request->column) ? "id" : $request->column;

        $contentCategory = $contentCategory->orderBy($request->column, $request->order);

        $contentCategory = $contentCategory->paginate($request["page"] ? (int)$request["limit"] : 10);
        foreach($contentCategory as $key => $category){
            $videos = $category->videos()->count();
            $albums = $category->albums()->count();
            $channels = $category->channels()->count();
            $tags = $category->tags()->select("content_tags.id", "content_tags.name")->get();
            $contentCategory[$key] = [
                'id' => $category["id"],
                'thumb' => "category-thumbnails/".$category["thumbnail"],
                'name' => $category["name"],
                'title_slug' => "/".$category["title_slug"],
                'status' => str_replace([1,2], ["Online", "Offline"], $category["status"]),
                'parent' => $category["parent_id"] > 0 ? array(ContentCategory::find($category["parent_id"])) : null,
                'videos' => $videos,
                'albums' => $albums,
                'channels' => $channels,
                'created_at' => date("m/d/Y H:i", strtotime($category->created_at)),
                'tags' => $tags,
                'seo_title' => $category["seo_title"],
                'seo_description' => $category["seo_description"],
                'seo_keywords' => $category["seo_keywords"],
            ];
        }
        return response()->json($contentCategory);
    }

    public static function indexPagination(Request $request)
    {
        $contentCategory = ContentCategory::withCount("videos");
        if($request["name"]){
            $contentCategory->where('name', 'LIKE', '%'.$request->name.'%');
        }
        if($request->column && $request->order){
            $contentCategory->orderBy($request->column, $request->order);
        }
        $collection = $contentCategory->paginate($request->limit); 
        return response()->json($collection);
    }

    public function search(Request $request)
    {
        $contentCategory = ContentCategory::select("id", "name");
        if($request["selected"]){
            $ids = explode(",", $request["selected"]);
            foreach ($ids as $key => $id) {
                $contentCategory->where('id', "!=", $id);
            }
        }
        if($request["q"]){
            $contentCategory->where('name', 'LIKE', '%'.$request->q.'%');
        }
        $contentCategory = $contentCategory->orderBy("name", "asc")->limit(10)->get()->toArray(); 
        return response()->json($contentCategory);
    }

    public function show($id)
    {
        $parentCategories = ContentCategory::where('parent_id', 0)->orWhereNull('parent_id');
        if($id !== 0){
            $parentCategories = $parentCategories->where('id', '!=', $id);
        }
        $parentCategories = $parentCategories->get()->toArray();
        $meta = [
            "parent_id" => $parentCategories,
        ];

        if($id !== "new" && $id !== 0){
            if(ctype_digit($id)){
                $contentCategory = ContentCategory::find($id);
            }
            else {
                $contentCategory = ContentCategory::where('title_slug', $id)->first();
            }

            if (!$contentCategory) {
                abort(404);
            }

            $tags = $contentCategory->tags()
                ->select('content_tags.id', 'content_tags.name', 'content_tags.title_slug')
                ->where('status', '!=', 2)
                ->get();
            $videos = $contentCategory->videos()->count();
            $albums = $contentCategory->albums()->count();
            $channels = $contentCategory->channels()->count();
            $contentCategory= $contentCategory->toArray();
            foreach ($contentCategory as &$value) {
                if($value === "null" || !$value){
                    $value = "";
                }
            }
            $contentCategory["name"] = ucwords($contentCategory["name"]);
            $contentCategory["status"] = (int)$contentCategory["status"];
            $contentCategory["current_thumbnail"] = asset("category-thumbnails/".$contentCategory["thumbnail"]);
            $contentCategory["tags"] = $tags;
            $contentCategory["meta"] = $meta;
            $contentCategory["videos"] = $videos;
            $contentCategory["albums"] = $albums;
            $contentCategory["channels"] = $channels;
            $contentCategory["created_at"] = date("m/d/Y", strtotime($contentCategory["created_at"]));
            $contentCategory["parent"] = ContentCategory::find((int)$contentCategory["parent_id"]);
            return response()->json($contentCategory);
        }
        return response()->json([
            'meta' => $meta
        ]);
    }

    public function delete($id)
    {
        $contentCategory = ContentCategory::find($id);
        $contentCategory->delete();
        $contentCategoryChildren = ContentCategory::where("parent_id", $id)->get();
        foreach ($contentCategoryChildren as $key => $category) {
            $child = ContentCategory::find($category->id);
            $child->update(["parent_id" => 0]);
        }
        return response()->json($contentCategory);
    }

    public function deleteBulk(Request $request)
    {
        $ids = explode(",", $request->items);
        foreach($ids as $item){
            $category = ContentCategory::find($item);
            $category->delete();
        }
        return response()->json([
            "error_message" => "Item/s have been deleted!"
        ]);
    }

    public static function getCategoriesId($categories){
        foreach ($categories as &$category) {
            if (substr($category, 0, 1) === "0") {
                $checkExisting = ContentCategory::where('name', 'LIKE', substr($category, 1))->whereRaw('LOWER(name) = ?', [strtolower(substr($category, 1))])->first();
                if($checkExisting){
                    $category = $checkExisting->id;
                }
                else {
                    $newCategory = new ContentCategory([
                        "name" => substr($category, 1),
                        "title_slug" => UtilsController::createSlug(substr($category, 1), "content_categories", "title_slug"),
                        "status" => 1
                    ]);
                    $newCategory->save();   
                    $category = $newCategory->id;
                }
            }
        }
        return array_unique($categories);
    }
}
