<?php

namespace App\Http\Controllers;

use App\Http\Controllers\UtilsController;
use App\Models\Content;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function store(Request $request)
    {
        $data = UtilsController::convertNullStringsToNull($request->all(), true);
        foreach ($data as $key => $value) {
            if(Str::startsWith($key, 'file')){
                if(gettype($value) === "object"){
                    $value->move(public_path("assets"), $key . ".png");
                    $value = 1;
                }
            }
            $item = Settings::where("key", $key)->first();
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            if($item){
                if($item->value !== $value){
                    $item->update(["value" => $value]);
                }
            }
            else {
                $item = new Settings(["key" => $key, "value" => $value]);
                $item->save();
            }
        }
        $settings = Settings::get();
        return response()->json($settings);
    }

    public function index(){
        $settings = Settings::orderBy("id", "desc")->get();
        $result = array();
        foreach ($settings as $item) {
            $result[$item->key] = $item->value;
        }
        $result["sitemap"] = url("/sitemap.xml");
        return response()->json($result);
    }

    public static function indexFrontend(){
        $settings = Settings::get();
        $result = array();
        foreach ($settings as $item) {
            if(in_array($item->key, [
                "adultWarning",
                "cookiesNotification",
                "cookiesNotificationContent",
                "adultWarningContent",
                "units",
                "siteTitle",
                "siteTagline",
                "siteDescription",
                "mainMenu",
                "socialMedia",
                "backLinks",
                "disableWebsite",
                "disableWebsiteContent",
                "themeColorPrimary",
                "themeColorPrimaryLight",
                "themeColorPrimaryDark",
                "themeColorLink",
                "themeColorButton",
                "themeColorWhite",
                "themeColorBlack",
                "seoDescription",
                "siteKeywords",
                "javascriptIntegration",
                "verificationCode",
                "p404Heading",
                "p404Subheading"
            ])){
                $result[$item->key] = $item->value;
            }
            $result["customPagesIds"] = Content::select(["id"])->where("status", 1)->pluck("id");
        }
        $result = UtilsController::convertNullStringsToNull($result);
        return response()->json($result);
    }

    public function removeKey(Request $request){
        $settings = Settings::where("key", $request->key)->first();
        if($settings){
            $settings->delete();
            return $request->key." was deleted!";
        }
        return "Key was not found!";
    }
}
