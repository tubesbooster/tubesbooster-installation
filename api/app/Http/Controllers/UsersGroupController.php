<?php

namespace App\Http\Controllers;

use App\Models\UsersGroup;
use Illuminate\Http\Request;

class UsersGroupController extends Controller
{
    public function store(Request $request)
    {
        if($request->id){
            $usersGroup = UsersGroup::find($request->id);
            $usersGroup->update($request->all());
        }
        else {
            $usersGroup = new UsersGroup($request->all());
            $usersGroup->save();   
        }
        return response()->json(['data' => $usersGroup]);
    }

    public function index(Request $request)
    {
        $usersGroup = UsersGroup::query();
        if($request["id"]){
            $usersGroup->where('id', $request->id);
        }
        if($request["name"]){
            $usersGroup->where('name', 'LIKE', '%'.$request->name.'%');
        }
        $usersGroup = $usersGroup->paginate(10); 
        return response()->json($usersGroup);
    }

    public function show($id)
    {
        if($id !== "new"){
            $usersGroup = UsersGroup::find($id);
            return response()->json($usersGroup);
        }
    }

    public function delete($id)
    {
        $usersGroup = UsersGroup::find($id);
        $usersGroup->delete();
        return response()->json($usersGroup);
    }
}
