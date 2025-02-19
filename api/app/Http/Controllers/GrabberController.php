<?php

namespace App\Http\Controllers;

use App\Models\Grabber;
use Illuminate\Http\Request;

class GrabberController extends Controller
{
    public function index()
    {
        $grabbers = Grabber::query();
        $collection = $grabbers->paginate(100); 

        foreach($collection as $key => $grabber){
            $collection[$key] = [
                'id' => $grabber->id,
                'platform' => $grabber->platform,
                'description' => $grabber->description && $grabber->description !== "null" ? $grabber->description : "",
                'process' => $grabber->queue > 0 ? "<div class='import-status import-status-queue'>$grabber->queue in queue</div>" : "<div class='import-status import-status-".($grabber->new == 1 ? "added" : "finished")."'>".($grabber->new == 1 ? "Added" : "Finished")."</div>",
                'importType' => str_replace([1,2], ["Embed", "Download"], $grabber->importType),
                'status' => str_replace([1,2], ["Online", "Offline"], $grabber->status),
                'type' => str_replace([1,2,3], ["Public", "Private", "Premium"], $grabber->type),
                'duplicated' => str_replace([1,2], ["Do not grab", "Grab all"], $grabber->duplicated),
                'data' => str_replace([1,2,3,4,5,6,7,8,9, "0,"], ["Title", "Description", "Rating", "Views", "Models", "Date added", "Channel", "Categories", "Tags", ""], $grabber->data),
                'utils' => "<div class='grabber_list_btn'>Add source</div>"
            ];
        }

        return response()->json($collection);
    }

    public function store(Request $request)
    {
        if($request->id){
            $grabber = Grabber::findOrFail($request->id);
            $grabber->update($request->all());        
        }
        else {
            $grabber = Grabber::create($request->all());
            $grabber->update(["new" => 1]);     
        }

        return response()->json($grabber, 201);
    }

    public function show($id)
    {
        $grabber = Grabber::findOrFail($id)->toArray();
        $grabber["type"] = (int)$grabber["type"];
        $grabber["status"] = (int)$grabber["status"];
        $grabber["importType"] = (int)$grabber["importType"];
        $grabber["duplicated"] = (int)$grabber["duplicated"];
        $grabber["title_limit"] = $grabber["title_limit"] && $grabber["title_limit"] !== "null" ? $grabber["title_limit"] : "";
        $grabber["description_limit"] = $grabber["description_limit"] && $grabber["description_limit"] !== "null" ? $grabber["description_limit"] : "";
        $grabber["data"] = explode(",",$grabber["data"]);
        $grabber["description"] = $grabber["description"] && $grabber["description"] !== "null" ? $grabber["description"] : "";
        foreach(array_keys($grabber["data"]) as $grabberDataKey){
            $grabber["data"][$grabberDataKey] = (int)$grabber["data"][$grabberDataKey];
        }
        return response()->json($grabber);
    }

    public function update(Request $request, $id)
    {
        
    }

    public function destroy(Request $request)
    {
        $ids = explode(",", $request->items);
        foreach($ids as $item){
            $grabber = Grabber::find($item);
            $grabber->delete();
        }
        return response()->json($grabber);
    }
}