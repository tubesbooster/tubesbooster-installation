<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ContentController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'messages' => $e->errors()], 422);
        }

        $data = array_merge($request->all(), $validatedData);

        if($request->id){
            $page = Content::find($request->id);
            $page->update($data);
        }
        else {
            $page = new Content($data);
            $page->save();   
        }

        return response()->json(['data' => $page]);
    }

    public function index(Request $request)
    {
        $pages = Content::query();

        //Load default if undefined properly
        $request->limit = $request->limit === "undefined" ? 10 : (int)$request->limit;
        
        $pages = $pages->paginate($request->limit ? $request->limit : 10);

        foreach ($pages as $page) {
            $page->status = str_replace([1,2], ["Online", "Offline"], $page->status);
        }

        return response()->json($pages);
    }

    public function show($id)
    {
        $page = Content::find($id);

        $page->status = (int)$page->status;

        return response()->json($page);
    }

    public function destroy(Request $request)
    {
        $ids = explode(",", $request->items);
        foreach($ids as $item){
            $page = Content::find($item);
            $page->delete();
        }
        return response()->json([
            "error_message" => "Item/s have been deleted!"
        ]);
    }
}
