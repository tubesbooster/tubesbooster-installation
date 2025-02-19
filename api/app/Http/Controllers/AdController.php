<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'messages' => $e->errors()], 422);
        }

        $data = array_merge($request->all(), $validatedData);
        $data = UtilsController::convertNullStringsToNull($data);

        if(empty($data["type"])){
            return response()->json([
                'error_message' => "Placement of advertisement is required!"
            ]);
        }
        
        if($data["scheduled_from"]){
            $date = new \DateTime($data["scheduled_from"]);
            $formattedDate = $date->format('Y-m-d H:i');
            $data["scheduled_from"] = $formattedDate;
        }
        
        if($data["scheduled_to"]){
            $date = new \DateTime($data["scheduled_to"]);
            $formattedDate = $date->format('Y-m-d H:i');
            $data["scheduled_to"] = $formattedDate;
        }

        if($request->id){
            $ad = Ad::find($request->id);
            $ad->update($data);

            $ad->categories()->detach();
            if($request->categories && count($request->categories) > 0){
                $categoriesIds = ContentCategoryController::getCategoriesId($request->categories);
                $ad->categories()->attach($categoriesIds);
            }
        }
        else {
            $ad = new Ad($data);
            $ad->save();  

            if($request->categories && count($request->categories) > 0){
                $categoriesIds = ContentCategoryController::getCategoriesId($request->categories);
                $ad->categories()->attach($request->categories);
            } 
        }

        return response()->json(['data' => $ad]);
    }

    public static function index(Request $request)
    {
        $ads = Ad::query();
        if($request["name"]){
            $ads->where('name', 'LIKE', '%'.$request->name.'%');
        }
        $categories = json_decode($request["categories"]);
        if($request["categories"]){
            if(!empty($categories)){
                $ads->whereHas('categories', function ($subquery) use ($categories) {
                    $subquery->whereIn('content_category_id', array_map(function($category) {
                        return $category->id;
                    }, $categories));
                });
            }
        }
        if($request["status"]){
            $ads->where('status', $request->status);
        }
        
        //Load default if undefined properly
        $request->order = $request->order !== "desc" ? "asc" : "desc";
        $request->limit = $request->limit === "undefined" ? 10 : (int)$request->limit;
        $request->column = $request->column === "undefined" ? "id" : $request->column;
        
        $ads = $ads->orderBy($request->column, $request->order); 
        $ads = $ads->paginate($request->limit ? $request->limit : 10);
        foreach($ads as $key => $ad){
            $ads[$key] = [
                "type" => $ad->type,
                "id" => $ad->id,
                "name" => $ad->name,
                "status" => str_replace([1,2], ["Active", "Inactive"], $ad->status),
                "views" => $ad->views,
                "categories" => $ad->categories,
                "scheduled_from" => empty($ad->scheduled_from) ? "" : date("m/d/Y h:i", strtotime($ad->scheduled_from)),
                "scheduled_to" => empty($ad->scheduled_to) ? "" : date("m/d/Y h:i", strtotime($ad->scheduled_to))
            ];
        }
        return response()->json($ads);
    }

    public function show($id)
    {
        $ad = Ad::with('categories')->find($id);

        return response()->json($ad);
    }

    public static function showFrontend($categories, $type, $limit){
        $ads = Ad::query();
        if(!empty($categories)){
            $ads->whereHas('categories', function ($subquery) use ($categories) {
                $subquery->whereIn('content_category_id', array_map(function($category) {
                    return $category["id"];
                }, $categories));
            })
            ->orWhereDoesntHave('categories');
        }
        $ads = $ads->where("type", $type)
            ->where(function ($query) {
                $query->where('scheduled_from', '<=', now())
                      ->orWhereNull('scheduled_from');
            })
            ->where(function ($query) {
                $query->where('scheduled_to', '>', now())
                      ->orWhereNull('scheduled_to');
            })
            ->where('status', 1)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
        foreach ($ads as $key => $ad) {
            $ad->increment('views');
            $ad["ad"] = true;
        }
        return $ads;
    }

    public function destroy(Request $request)
    {
        $ids = explode(",", $request->items);
        foreach($ids as $item){
            $ad = Ad::find($item);
            $ad->delete();
        }
        return response()->json([
            "error_message" => "Item/s have been deleted!"
        ]);
    }
}
