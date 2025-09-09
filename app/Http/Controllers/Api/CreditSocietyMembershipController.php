<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditSocietyMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditSocietyMembershipController extends Controller
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
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = CreditSocietyMembership::with('employee');

        $query->when(
            request('employee_id'),
            fn($q) => $q->where('employee_id', request('employee_id'))
        );

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        // Check if user has required roles
        if (!$this->user->hasAnyRole($this->all_permission_roles)) {
            return response()->json(['errorMsg' => 'Access Denied! Only IT Admin and Director can perform this action.'], 403);
        }

        $request->validate([
            'employee_id' => 'required|numeric|exists:employees,id',
            'society_name' => 'required|string',
            'membership_number' => 'required|string',
            'joining_date' => 'required|date',
            'relieving_date' => 'nullable|date',
            'monthly_subscription' => 'required|numeric',
            'entrance_fee' => 'required|numeric',
            'is_active' => 'boolean|in:1,0',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'remark' => 'nullable|string|max:255'
        ]);

        $employeeCredit = CreditSocietyMembership::where('employee_id', $request['employee_id'])->get()->last();

        DB::beginTransaction();
        if ($employeeCredit) {
            $employeeCredit->effective_till = \Carbon\Carbon::parse($request['effective_from'])->subDay()->format('Y-m-d');

            try {
                $employeeCredit->save();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['errorMsg' => $e->getMessage()], 500);
            }
        }

        $creditSocietyMembership = new CreditSocietyMembership();
        $creditSocietyMembership->employee_id = $request['employee_id'];
        $creditSocietyMembership->society_name = $request['society_name'];
        $creditSocietyMembership->membership_number = $request['membership_number'];
        $creditSocietyMembership->joining_date = $request['joining_date'];
        $creditSocietyMembership->relieving_date = $request['relieving_date'];
        $creditSocietyMembership->monthly_subscription = $request['monthly_subscription'];
        $creditSocietyMembership->entrance_fee = $request['entrance_fee'];
        $creditSocietyMembership->is_active = $request['is_active'];
        $creditSocietyMembership->effective_from = $request['effective_from'];
        $creditSocietyMembership->effective_till = $request['effective_till'];
        $creditSocietyMembership->added_by = auth()->id();
        $creditSocietyMembership->remark = $request['remark'];

        try {
            $creditSocietyMembership->save();
            db::commit();
            return response()->json(['successMsg' => 'Credit Society Membership Created!', 'data' => $creditSocietyMembership]);
        } catch (\Exception $e) {
            db::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        // Check if user has required roles
        if (!$this->user->hasAnyRole($this->all_permission_roles)) {
            return response()->json(['errorMsg' => 'Access Denied! Only IT Admin and Director can perform this action.'], 403);
        }

        $creditSocietyMembership = CreditSocietyMembership::find($id);
        if (!$creditSocietyMembership) return response()->json(['errorMsg' => 'Credit Society Membership not found!'], 404);

        $request->validate([
            'employee_id' => 'required|numeric|exists:employees,id',
            'society_name' => 'required|string',
            'membership_number' => 'required|string',
            'joining_date' => 'required|date',
            'relieving_date' => 'nullable|date',
            'monthly_subscription' => 'required|numeric',
            'entrance_fee' => 'required|numeric',
            'is_active' => 'boolean|in:1,0',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'remark' => 'nullable|string|max:255'
        ]);

        $creditSocietyMembership->employee_id = $request['employee_id'];
        $creditSocietyMembership->society_name = $request['society_name'];
        $creditSocietyMembership->membership_number = $request['membership_number'];
        $creditSocietyMembership->joining_date = $request['joining_date'];
        $creditSocietyMembership->relieving_date = $request['relieving_date'];
        $creditSocietyMembership->monthly_subscription = $request['monthly_subscription'];
        $creditSocietyMembership->entrance_fee = $request['entrance_fee'];
        $creditSocietyMembership->is_active = $request['is_active'];
        $creditSocietyMembership->effective_from = $request['effective_from'];
        $creditSocietyMembership->effective_till = $request['effective_till'];
        $creditSocietyMembership->edited_by = auth()->id();
        $creditSocietyMembership->remark = $request['remark'];

        try {
            $creditSocietyMembership->save();

            return response()->json(['successMsg' => 'Credit Society Membership Created!', 'data' => $creditSocietyMembership]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
