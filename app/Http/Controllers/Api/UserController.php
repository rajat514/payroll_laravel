<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    function login(Request $request)
    {
        $request->validate([
            'username' => 'required|email|max:191',
            'password' => 'required|min:5'
        ]);

        if (auth()->attempt(['email' => $request['username'], 'password' => $request['password'], 'is_active' => 1])) 
        {
            $user = User::find(auth()->id());

            $token = $user->createToken('api');

            return response()->json(['token' => $token->plainTextToken]);
        } else {
            return response()->json(['errorMsg' => 'Invalid Credentials!'], 422);
        }
    }

    function user()
    {
        $user = User::with('role')->find(auth()->id());
        return response()->json(['data' => $user]);
    }
}
