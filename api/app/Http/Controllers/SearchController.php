<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\ModelController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\ContentCategoryController;

class SearchController extends Controller {
    public static function search(Request $request){
        $videos = VideoController::index(new Request([
            "limit" => 9,
            "name" => $request->name
        ]));
        $albums = AlbumController::index(new Request([
            "limit" => 6,
            "name" => $request->name
        ]));
        $models = ModelController::index(new Request([
            "limit" => 6,
            "name" => $request->name
        ]));
        $channels = ChannelController::index(new Request([
            "limit" => 6,
            "name" => $request->name
        ]));
        $categories = ContentCategoryController::indexPagination(new Request([
            "limit" => 10,
            "name" => $request->name
        ]));
        return response()->json([
            "videos" => $videos->original,
            "albums" => $albums->original,
            "models" => $models->original,
            "categories" => $categories->original,
            "channels" => $channels->original
        ]);
    }
}