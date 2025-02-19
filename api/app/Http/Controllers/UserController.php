<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Video;
use App\Models\Country;
use App\Models\Language;
use App\Http\Controllers\UtilsController;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(Request $request)
    {
        if(gettype($request->avatar) === "object"){
            $ext = explode(".", $request->avatar->getClientOriginalName());
            $filename = $request->username.'-'.md5(time()).'.'.end($ext);
            $request->avatar->move(public_path("avatars") ,$filename);
            $request->avatar = $filename;
        }
        elseif(gettype($request->avatar) === "string") {
            $request->avatar = false;
        }
        else {
            $request->avatar = " ";
        }
        if(gettype($request->cover) === "object"){
            $ext = explode(".", $request->cover->getClientOriginalName());
            $filename = $request->username.'-cover-'.md5(time()).'.'.end($ext);
            $request->cover->move(public_path("avatars") ,$filename);
            $request->cover = $filename;
        }
        elseif(gettype($request->cover) === "string") {
            $request->cover = false;
        }
        else {
            $request->cover = " ";
        }
        if($request->password){
            $password = md5($request->password);
        }
        else {
            $password = md5("test");
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
        $data = $request->all();
        $data = UtilsController::convertNullStringsToNull($data);
        if($request->id){
            $user = User::find($request->id);
            if($data["password"]){
                $data["password"] = $password;   
            }
            else {
                $data["password"] = $user->password;   
            }
            if($data["avatar"] === false){
                $data["avatar"] = $user->avatar;
            }
            if($data["cover"] === false){
                $data["cover"] = $user->cover;
            }
            $user->update(array_merge($data, [ 
                "avatar" => $request->avatar, 
                "cover" => $request->cover, 
                "country" => $country, 
                "languages" => $languages 
            ]));
        }
        else {
            $data["password"] = $password;
            $user = new User(array_merge($data, [ 
                "avatar" => $request->avatar, 
                "cover" => $request->cover, 
                "country" => $country, 
                "languages" => $languages 
            ]));
            $user->save();   
        }
        return response()->json(['data' => $user]);
    }

    public function index(Request $request)
    {
        $collection = User::query();
        if($request["name"]){
            $collection->where('username', 'LIKE', '%'.$request->name.'%')
                ->orWhere('email', 'LIKE', '%'.$request->name.'%')
                ->orWhere('display_name', 'LIKE', '%'.$request->name.'%');
        }
        if($request["status"] && $request["status"] != 0){
            $collection->where('status', $request["status"]);
        }
        
        //Load default if undefined properly
        $request->order = $request->order !== "desc" ? "asc" : "desc";
        $request->limit = $request->limit === "undefined" ? 10 : (int)$request->limit;
        $request->column = $request->column === "undefined" ? "id" : $request->column;
        
        switch($request->column){
            case "registration_date":
                $orderColumn = "id";
                break;
            case "content":
                $orderColumn = "video_count";
                break;
            default:
                $orderColumn = $request->column;
                break;
        }
        if($orderColumn == "video_count"){
            $collection = $collection->withCount('videos')->orderBy('videos_count', $request->order);
        }
        else {
            $collection = $collection->orderBy($orderColumn, $request->order);
        }
        $collection = $collection->paginate($request->limit); 
        foreach($collection as $key => $user){
            $albumCount = 0;
            $photoCount = 0;
            foreach ($user->albums()->get() as $album) {
                $albumCount++;
                $photoCount = $photoCount + $album->photos()->count();
            }
            $collection[$key] = [
                'id' => $user->id,
                'avatar' => "avatars/".$user->avatar,
                'username' => $user->username,
                'email' => $user->email,
                'display_name' => $user->display_name ? $user->display_name : $user->first_name." ".$user->last_name,
                'last_login' => 'never',
                'registration_date' => date("d.m.Y", strtotime($user->created_at)),
                'content' => Video::where('user_id', $user->id)->count()." / $photoCount",
                'display_nameAvatar' => "avatars/".$user->avatar,
                'registered' => "Registered: ".date("m/d/Y", strtotime($user->created_at)),
                'photosTotal' => "$albumCount / $photoCount",
                'followersTotal' => 0,
                'registeredStatus' => "Registered",
                'activityStatus' => str_replace([1,2,3,4], ["Active", "Inactive", "Premium", "Banned"], $user->status),
                'type' => "Public"
            ];
        }
        return response()->json($collection);
    }

    public function show($id)
    {
        if($id !== "new"){
            $userModel = User::findOrFail($id);
            $albumCount = 0;
            $photoCount = 0;
            foreach ($userModel->albums()->get() as $key => $album) {
                $albumCount++;
                $photoCount = $photoCount + $album->photos()->count();
            }
            $user = $userModel->toArray();
            $country = Country::find((int)$user["country"]);
            $user["website"] = $user["website"] === "null" ? "" : $user["website"];
            $user["status"] = (int)$user["status"];
            $user["first_name"] = $user["first_name"] === "null" ? "" : $user["first_name"];
            $user["last_name"] = $user["last_name"] === "null" ? "" : $user["last_name"];
            $user["about_me"] = $user["about_me"] === "null" ? "" : $user["about_me"];
            $user["country"] = $country ? [$country] : [];
            $user["city"] = $user["city"] === "null" ? "" : $user["city"];
            $user["current_avatar"] = asset("avatars/".$user["avatar"]);
            $user["current_cover"] = asset("avatars/".$user["cover"]);
            $user["created_at"] = date("n/j/Y", strtotime($user["created_at"]));
            $user["videos"] =  Video::where('user_id', $id)->count();
            $user["albums"] =  $albumCount;
            $user["photos"] =  $photoCount;
            $user["languages"]= $user["languages"] ? json_decode($user["languages"]) : [];
            $user["orientationText"] = str_replace([0,1,2,3,4,5], ["Rather not say", "Straight", "Gay", "Lesbian", "Not sure", "Bisexual"], $user["orientation"]);
            $user["relationshipText"] = str_replace([0,1,2,3,4,5], ["Rather not say", "Single", "Married", "Open", "Divorced", "Widowed"], $user["relationship"]);
            $user["educationText"] = str_replace([0,1,2,3,4,5,6,7,8,9,10], ["Rather not say", "High school", "High school graduate", "College", "Current college student", "Associate degree (2 years college)", "BA/BS (4 years college)", "Grad school", "Current grad school student", "PhD/MD/Post doctorate", "JD"], $user["education"]);
            $user["ethnicityText"] = str_replace([0,1,2,3,4,5,6,7], ["Rather not say", "Asian", "Black", "Indian", "Latino", "Middle Eastern", "Mixed", "White"], $user["ethnicity"]);
            $user["drinkingText"] = str_replace([0,1,2,3,4], ["Rather not say", "Never", "Occasionally", "Several times a week", "Most days"], $user["drinking"]);
            $user["hairLengthText"] = str_replace([0,1,2,3,4], ["Rather not say", "Short", "Medium", "Long", "Bald"], $user["hair_length"]);
            $user["hairColorText"] = str_replace([0,1,2,3,4,5], ["Rather not say", "Black", "Brown", "Blonde", "Red", "Gray"], $user["hair_color"]);
            $user["eyeColorText"] = str_replace([0,1,2,3,4,5], ["Rather not say", "Brown", "Blue", "Green", "Gray", "Black"], $user["eye_color"]);
            $user["smokingText"] = str_replace([0,1,2,3], ["Rather not say", "Never", "Occasionally", "Regularly"], $user["smoking"]);
            foreach ($user["languages"] as &$value) {
                $value = Language::find($value)->toArray();    
            }
            return response()->json($user);
        }
    }

    public function delete(Request $request)
    {
        $ids = explode(",", $request->items);
        foreach($ids as $item){
            if($id !== 1){
                $user = User::find($item);
                $user->delete();
            }
        }
        return response()->json($user);
    }

    public function search(Request $request)
    {
        $user = User::select('id', 'username as name');
        if($request["selected"]){
            $ids = explode(",", $request["selected"]);
            foreach ($ids as $key => $id) {
                $user->where('id', "!=", $id);
            }
        }
        if($request["q"]){
            $user->where('display_name', 'LIKE', '%'.$request->q.'%');
        }
        $user = $user->limit(10)->get()->toArray(); 
        return response()->json($user);
    }

    public function searchCountry(Request $request)
    {
        $country = Country::select('id', 'name');
        if($request["q"]){
            $country->where('name', 'LIKE', '%'.$request->q.'%');
        }
        $country = $country->limit(10)->get()->toArray(); 
        return response()->json($country);
    }

    public function searchLanguage(Request $request)
    {
        $language = Language::select('id', 'name');
        if($request["selected"]){
            $ids = explode(",", $request["selected"]);
            foreach ($ids as $key => $id) {
                $language->where('id', "!=", $id);
            }
        }
        if($request["q"]){
            $language->where('name', 'LIKE', '%'.$request->q.'%');
        }
        $language = $language->limit(50)->get()->toArray(); 
        return response()->json($language);
    }
}
