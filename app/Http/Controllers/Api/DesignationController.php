<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DesignationController extends Controller
{
    private \App\Models\User $user;

    private $all_permission_roles = ['IT Admin', 'Director', 'Salary Processing Coordinator (ROHC)', 'Salary Processing Coordinator (NIOH)'];

    function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = \App\Models\User::find(auth()->id());
            return $next($request);
        });
    }

    function index()
    {
        $data = Designation::orderBy('created_at', 'DESC')->get();
        return response()->json(['data' => $data]);
    }

    function show($id)
    {
        $data = Designation::with('history.addedBy.roles:id,name', 'history.editedBy.roles:id,name', 'addedBy.roles:id,name', 'editedBy.roles:id,name')->find($id);

        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        // Check if user has required roles
        if (!$this->user->hasAnyRole($this->all_permission_roles)) {
            return response()->json(['errorMsg' => 'Access Denied! Only IT Admin and Director can perform this action.'], 403);
        }

        $fields = $request->validate([
            'name' => 'required|string|max:191',
            'options' => 'nullable|array',
            'options.*' => 'string|max:100'
        ]);

        $fields['added_by'] = auth()->id();

        try {
            $designation = Designation::create($fields);
            return response()->json(['successMsg' => "Designation created!", 'data' => $designation]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        // Check if user has required roles
        if (!$this->user->hasAnyRole($this->all_permission_roles)) {
            return response()->json(['errorMsg' => 'Access Denied! Only IT Admin and Director can perform this action.'], 403);
        }

        $designation = Designation::find($id);
        if (!$designation) return response()->json(['errorMsg' => 'Designation not found!'], 404);

        $request->validate([
            'name' => 'required|string|max:191',
            'options' => 'nullable|array',
            'options.*' => 'string|max:100'
        ]);

        DB::beginTransaction();

        $old_data = $designation->toArray();

        $designation->name = $request['name'];
        $designation->options = $request['options'];
        $designation->edited_by = auth()->id();

        try {
            $designation->save();

            $designation->history()->create($old_data);
            DB::commit();
            // $designation = Designation::create($fields);
            return response()->json(['successMsg' => "Designation updated!", 'data' => $designation]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
