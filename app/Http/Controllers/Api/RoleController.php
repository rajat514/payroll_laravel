<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    function index()
    {
        $roles = \Spatie\Permission\Models\Role::all();

        return response()->json(['data' => $roles]);
    }

    // function store(Request $request)
    // {
    //     $user = User::find(auth()->id());
    //     if ($user->role_id != 1) {
    //         return response()->json(['errorMsg' => 'You are not allowed to do this!'], 401);
    //     }

    //     $request->validate([
    //         'name' => 'required|string'
    //     ]);

    //     $role = new Role();
    //     $role->name = $request['name'];

    //     try {
    //         $role->save();

    //         return response()->json(['successMsg' => 'Role Created!', 'data' => $role]);
    //     } catch (\Exception $e) {
    //         return response()->json(['errorMsg' => $e->getMessage()], 500);
    //     }
    // }

    // function update(Request $request, $id)
    // {
    //     $user = User::find(auth()->id());
    //     if ($user->role_id != 1) {
    //         return response()->json(['errorMsg' => 'You are not allowed to do this!'], 401);
    //     }

    //     $role = Role::find($id);
    //     if (!$role) {
    //         return response()->json(['errorMsg' => 'Role not find!'], 404);
    //     }

    //     $request->validate([
    //         'name' => 'required|string'
    //     ]);

    //     $role->name = $request['name'];

    //     try {
    //         $role->save();

    //         return response()->json(['successMsg' => 'Role Updated!', 'data' => $role]);
    //     } catch (\Exception $e) {
    //         return response()->json(['errorMsg' => $e->getMessage()], 500);
    //     }
    // }
}
