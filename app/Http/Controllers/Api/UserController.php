<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    function login(Request $request)
    {
        $request->validate([
            'username' => 'required|email|max:191',
            'password' => 'required|min:5'
        ]);

        if (auth()->attempt(['email' => $request['username'], 'password' => $request['password'], 'is_active' => 1])) {
            $user = User::find(auth()->id());

            $token = $user->createToken('api');

            return response()->json(['token' => $token->plainTextToken]);
        } else {
            return response()->json(['errorMsg' => 'Invalid Credentials!'], 422);
        }
    }

    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = User::with('role');

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function allUsers()
    {

        $query = User::with('role');

        $query->withCount('employee');

        $data = $query->get();

        return response()->json(['data' => $data,]);
    }

    function user()
    {
        $user = User::with('role')->find(auth()->id());
        return response()->json(['data' => $user]);
    }

    function changeStatus($id)
    {
        $userRole = User::with('role')->find(auth()->id());
        if ($userRole->role_id != 1) return response()->json(['errorMsg' => 'You are not allowed to do this!'], 401);

        $user = User::find($id);
        if (!$user) return response()->json(['errorMsg' => 'User not found!'], 404);

        if ($id == 1) return response()->json(['errorMsg' => 'Admin status can not be changed!'], 409);

        $user->is_active = !$user->is_active;

        try {
            $user->save();

            return response()->json(['successMsg' => 'Status successfully changed!']);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function store(Request $request)
    {
        // $userRole = User::with('role')->find(auth()->id());
        // if ($userRole->role_id != 1) return response()->json(['errorMsg' => 'You are not allowed to do this!'], 401);

        if ($request['role_id'] == 1) return response()->json(['errorMsg' => 'Admin already exists!'], 409);

        $request->validate([
            'role_id' => 'required|numeric|exists:roles,id',
            'first_name' => 'required|string|min:3|max:191',
            'middle_name' => 'nullable|string|min:3|max:191',
            'last_name' => 'required|string|min:3|max:191',
            'employee_code' => 'required|string|min:3|max:191|unique:users,employee_code',
            'password' => 'required|string|min:5|max:30',
            'email' => 'required|email|unique:users,email',
            'institute' => 'required|in:NIOH,ROHC,BOTH'
        ]);


        $user = new User();
        $user->role_id = $request['role_id'];
        $user->first_name = $request['first_name'];
        $user->middle_name = $request['middle_name'];
        $user->last_name = $request['last_name'];
        $user->employee_code = $request['employee_code'];
        $user->email = $request['email'];
        $user->institute = $request['institute'];
        $user->password = Hash::make($request['password']);

        try {
            $user->save();

            return response()->json(['successMsg' => 'User successfully created!', 'data' => $user]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $userRole = User::with('role')->find(auth()->id());
        if ($userRole->role_id != 1) return response()->json(['errorMsg' => 'You are not allowed to do this!'], 401);

        $user = User::find($id);
        if (!$user) return response()->json(['errorMsg' => 'User not found!'], 404);

        if ($request['role_id'] == 1 || $id == 1) return response()->json(['errorMsg' => 'Admin already exists!'], 409);

        $request->validate([
            'role_id' => 'required|numeric|exists:roles,id',
            'first_name' => 'required|string|min:3|max:191',
            'middle_name' => 'nullable|string|min:3|max:191',
            'last_name' => 'required|string|min:3|max:191',
            'employee_code' => "required|string|min:3|max:191|unique:users,employee_code,$id,id",
            'email' => "required|email|unique:users,email,$id,id",
            'institute' => 'required|in:NIOH,ROHC,BOTH'
        ]);

        $user->role_id = $request['role_id'];
        $user->first_name = $request['first_name'];
        $user->middle_name = $request['middle_name'];
        $user->last_name = $request['last_name'];
        $user->employee_code = $request['employee_code'];
        $user->email = $request['email'];
        $user->institute = $request['institute'];

        try {
            $user->save();

            return response()->json(['successMsg' => 'User successfully updated', 'data' => $user]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
