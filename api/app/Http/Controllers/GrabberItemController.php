<?php

namespace App\Http\Controllers;

use App\Models\GrabberItem;
use Illuminate\Http\Request;

class GrabberItemController extends Controller
{
    public function store(Request $request)
    {
        if($request->id){
            $item = GrabberItem::find($request->id);
            $item->update($request->all());
        }
        else {
            $item = new GrabberItem($request->all());
            $item->save();   
        }
        return response()->json(['data' => $item]);
    }

    public function index(Request $request)
    {
        $items = GrabberItem::select(["id", "grabber_id", "url", "status", "created_at", "updated_at", "message"])->where("grabber_id", $request->grabber_id)->orderBy("id", "desc");
        $items = $items->paginate((int)$request->limit ? (int)$request->limit : 10);
        foreach ($items as &$item) {
            $item->itemStatus = str_replace([1,2,3], [
                "<div class='data-table-blue-text'>Awaiting result</div>",
                "<div class='data-table-green-text'>Completed</div>",
                "<div class='data-table-red-text'>Failed</div>"
            ], $item->status);
            $item->started = date("m/d/Y h:i:s", strtotime($item->created_at));
            $item->finished = $item->status === 1 ? "" : date("m/d/Y h:i:s", strtotime($item->updated_at));
            $item->message = $item->message !== "" ? 1 : 0;
        }
        return response()->json($items);
    }

    public function show(Request $request)
    {
        $item = GrabberItem::select(["id", "message"])->where("id", $request->id)->first();
        return response()->json($item);
    }
}
