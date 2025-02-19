<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ModelController;
use App\Http\Controllers\UsersGroupController;
use App\Http\Controllers\ContentCategoryController;
use App\Http\Controllers\ContentTagController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\GrabberController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\AlbumImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\AdController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\GrabberItemController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UtilsController;
use App\Http\Controllers\StaticController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//CPanel - Users Groups
Route::get('users-group/{id}', [UsersGroupController::class, 'show']);
Route::delete('users-group/{id}', [UsersGroupController::class, 'delete']);
Route::post('users-group', [UsersGroupController::class, 'store']);
Route::get('users-groups', [UsersGroupController::class, 'index']);

//CPanel - Content Categories
Route::get('content-category/{id}', [ContentCategoryController::class, 'show']);
Route::delete('content-category/{id}', [ContentCategoryController::class, 'delete']);
Route::post('content-category', [ContentCategoryController::class, 'store']);
Route::get('content-categories', [ContentCategoryController::class, 'index']);
Route::get('content-categories-search', [ContentCategoryController::class, 'search']);
Route::post('content-category-delete', [ContentCategoryController::class, 'deleteBulk']);
Route::get('content-categories-cpanel', [ContentCategoryController::class, 'indexAdmin']);

//CPanel - Content Tags
Route::get('content-tag/{id}', [ContentTagController::class, 'show']);
Route::post('content-tag-delete', [ContentTagController::class, 'delete']);
Route::post('content-tag', [ContentTagController::class, 'store']);
Route::get('content-tags', [ContentTagController::class, 'index']);
Route::get('content-tags-cpanel', [ContentTagController::class, 'indexAdmin']);
Route::get('content-tags-search', [ContentTagController::class, 'search']);
Route::get('content-tags-delete-all', [ContentTagController::class, 'deleteAll']);

//cPanel - Users
Route::post('model', [ModelController::class, 'store']);
Route::get('models', [ModelController::class, 'index']);
Route::get('model/{id}', [ModelController::class, 'show']);
Route::post('model-delete', [ModelController::class, 'delete']);
Route::get('models-search', [ModelController::class, 'search']);

//cPanel - Users
Route::post('user', [UserController::class, 'store']);
Route::get('users', [UserController::class, 'index']);
Route::get('user/{id}', [UserController::class, 'show']);
Route::post('user-delete', [UserController::class, 'delete']);
Route::get('users-search', [UserController::class, 'search']);
Route::get('countries-search', [UserController::class, 'searchCountry']);
Route::get('languages-search', [UserController::class, 'searchLanguage']);

//cPanel - Videos
Route::post('video', [VideoController::class, 'store']);
Route::get('video-preview/{id}/{hash}', [VideoController::class, 'showPreview']);
Route::get('video/{id}', [VideoController::class, 'show']);
Route::get('videos', [VideoController::class, 'index']);
Route::post('video-delete', [VideoController::class, 'delete']);
Route::post('videos-bulk-edit', [VideoController::class, 'bulkEdit']);
Route::post('thumbnail-video', [VideoController::class, 'updateThumbnail']);
Route::post('custom-thumbnail-video', [VideoController::class, 'customThumbnail']);
Route::post('video-like', [VideoController::class, 'like']);

//cPanel - Grabber
Route::post('grabber-youtube', [ImportController::class, 'youtube']);
Route::post('grabber-xvideos', [ImportController::class, 'xvideos']);
Route::post('grabber-pornhub', [ImportController::class, 'pornhub']);
Route::post('grabber-xhamster', [ImportController::class, 'xhamster']);
Route::post('download', [ImportController::class, 'download']);
Route::get('import-progress', [ImportController::class, 'progress']);
Route::post('grabber', [GrabberController::class, 'store']);
Route::get('grabber/{id}', [GrabberController::class, 'show']);
Route::get('grabbers', [GrabberController::class, 'index']);
Route::get('grabbers-reset-queue', [ImportController::class, 'resetQueue']);
Route::post('grabber-delete', [GrabberController::class, 'destroy']);
Route::get('grabber-history/{grabber_id}', [GrabberItemController::class, 'index']);
Route::get('grabber-history-message/{id}', [GrabberItemController::class, 'show']);

//cPanel - Albums
Route::post('album', [AlbumController::class, 'store']);
Route::get('album/{id}', [AlbumController::class, 'show']);
Route::get('albums', [AlbumController::class, 'index']);
Route::post('album-delete', [AlbumController::class, 'delete']);
Route::delete('photo/{slug}', [AlbumController::class, 'deletePhoto']);
Route::post('albums-bulk-edit', [AlbumController::class, 'bulkEdit']);
Route::post('album-import-pornhub', [AlbumImportController::class, 'pornhub']);
Route::post('album-import-xhamster', [AlbumImportController::class, 'xhamster']);
Route::post('album-import-xvideos', [AlbumImportController::class, 'xvideos']);
Route::post('album-like', [AlbumController::class, 'like']);

//cPanel - Dashboard
Route::get('performance', [DashboardController::class, 'getPerformance']);
Route::post('login', [DashboardController::class, 'tempLogin']);
Route::post('delete-asset', [DashboardController::class, 'deleteAsset']);

//cPanel - Channels
Route::post('channel', [ChannelController::class, 'store']);
Route::get('channels', [ChannelController::class, 'index']);
Route::get('channel/{id}', [ChannelController::class, 'show']);
Route::post('channel-delete', [ChannelController::class, 'destroy']);
Route::get('channels-search', [ChannelController::class, 'search']);

//cPanel - Ads
Route::post('ad', [AdController::class, 'store']);
Route::get('ads', [AdController::class, 'index']);
Route::get('ad/{id}', [AdController::class, 'show']);
Route::post('ad-delete', [AdController::class, 'destroy']);

//cPanel - Content
Route::post('content', [ContentController::class, 'store']);
Route::get('content', [ContentController::class, 'index']);
Route::get('content/{id}', [ContentController::class, 'show']);
Route::post('content-delete', [ContentController::class, 'destroy']);

//cPanel - Settings
Route::post('settings', [SettingsController::class, 'store']);
Route::get('settings', [SettingsController::class, 'index']);
Route::get('fe-settings', [SettingsController::class, 'indexFrontend']);
Route::post('settings-remove-key', [SettingsController::class, 'removeKey']);

//cPanel - Frontend
Route::get('sidebar', [FrontendController::class, 'sidebar']);
Route::get('home', [FrontendController::class, 'home']);
Route::post('header', [FrontendController::class, 'header']);

Route::post('user-login', [UserController::class, 'show']);
//Route::post('upload-video', [VideoController::class, 'store'])->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Frontend - Frontend
Route::get('search', [SearchController::class, 'search']);

//Frontend - Frontend
Route::get('static', [StaticController::class, 'showStatic']);

//Frontend - Test
Route::get('php', [UtilsController::class, 'php']);

