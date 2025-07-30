<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayCommission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayCommissionController extends Controller
{
    private \App\Models\User $user;

    private $all_permission_roles = ['IT Admin', 'Director', 'Salary Processing Coordinator (NIOH)', 'Salary Processing Coordinator (ROHC)'];

    function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = \App\Models\User::find(auth()->id());
            return $next($request);
        });
    }

    function index()
    {
        $data = PayCommission::with('payMatrixLevel.payMatrixCell')->get();

        return response()->json(['data' => $data]);
    }

    function show($id)
    {
        $data = PayCommission::with('history.addedBy.roles:id,name', 'history.editedBy.roles:id,name', 'addedBy.roles:id,name', 'editedBy.roles:id,name')->find($id);

        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        // Check if user has required roles
        if (!$this->user->hasAnyRole($this->all_permission_roles)) {
            return response()->json(['errorMsg' => 'Access Denied! Only IT Admin and Director can perform this action.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:191',
            'year' => 'required|numeric|digits:4',
            'is_active' => 'required|in:1,0'
        ]);

        $payCommission = new PayCommission();
        $payCommission->name = $request['name'];
        $payCommission->year = $request['year'];
        $payCommission->is_active = $request['is_active'];
        $payCommission->added_by = auth()->id();

        try {
            $payCommission->save();

            return response()->json(['successMsg' => 'Pay Commission added!', 'data' => $payCommission]);
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

        $payCommission = PayCommission::find($id);
        if (!$payCommission) return response()->json(['errorMsg' => 'Pay Commission not found!'], 404);

        $request->validate([
            'name' => 'required|string|max:191',
            'year' => 'required|numeric|digits:4',
            'is_active' => 'required|in:1,0'
        ]);

        DB::beginTransaction();

        $old_data = $payCommission->toArray();

        $payCommission->name = $request['name'];
        $payCommission->year = $request['year'];
        $payCommission->is_active = $request['is_active'];
        $payCommission->edited_by = auth()->id();

        try {
            $payCommission->save();

            $payCommission->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Pay Commission updated!', 'data' => $payCommission]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
