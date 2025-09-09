<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DearnesAllowanceRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DearnessAllowanceRateController extends Controller
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

        $query = DearnesAllowanceRate::query();

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function show($id)
    {
        $data = DearnesAllowanceRate::with('history.addedBy.roles:id,name', 'history.editedBy.roles:id,name', 'addedBy.roles:id,name', 'editedBy.roles:id,name')->find($id);

        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        if (!$this->user->hasAnyRole($this->all_permission_roles)) {
            return response()->json(['errorMsg' => 'Access Denied! Only IT Admin and Director can perform this action.'], 403);
        }

        $request->validate([
            'rate_percentage' => 'required|numeric',
            'pwd_rate_percentage' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'notification_ref' => 'nullable|string'
        ]);

        $isSmallDate = DearnesAllowanceRate::where('effective_from', '>=', $request['effective_from'])->get()->first();
        if ($isSmallDate) return response()->json(['errorMsg' => 'Effective From date is smaller than previous!'], 400);

        $dearness = DearnesAllowanceRate::get()->last();

        DB::beginTransaction();

        if ($dearness) {
            $dearness->effective_till = \Carbon\Carbon::parse($request['effective_from'])->subDay()->format('Y-m-d');

            try {
                $dearness->save();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['errorMsg' => $e->getMessage()], 500);
            }
        }

        $dearnessAllowance = new DearnesAllowanceRate();
        $dearnessAllowance->rate_percentage = $request['rate_percentage'];
        $dearnessAllowance->pwd_rate_percentage = $request['pwd_rate_percentage'];
        $dearnessAllowance->effective_from = $request['effective_from'];
        $dearnessAllowance->effective_till = $request['effective_till'];
        $dearnessAllowance->notification_ref = $request['notification_ref'];
        $dearnessAllowance->added_by = auth()->id();

        try {
            $dearnessAllowance->save();
            DB::commit();
            return response()->json(['successMsg' => 'Dearness Allowance Rate Created!', 'data' => $dearnessAllowance]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        // Check if user has required roles
        if (!$this->user->hasAnyRole($this->all_permission_roles)) {
            return response()->json(['errorMsg' => 'Access Denied! Only IT Admin and Director can perform this action.'], 403);
        }

        $dearnessAllowance = DearnesAllowanceRate::find($id);
        if (!$dearnessAllowance) return response()->json(['errorMsg' => 'Dearness Allowance Rate not found!'], 404);

        $request->validate([
            'rate_percentage' => 'required|numeric',
            'pwd_rate_percentage' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'notification_ref' => 'nullable|string'
        ]);

        DB::beginTransaction();

        $old_data = $dearnessAllowance->toArray();

        $dearnessAllowance->rate_percentage = $request['rate_percentage'];
        $dearnessAllowance->pwd_rate_percentage = $request['pwd_rate_percentage'];
        $dearnessAllowance->effective_from = $request['effective_from'];
        $dearnessAllowance->effective_till = $request['effective_till'];
        $dearnessAllowance->notification_ref = $request['notification_ref'];
        $dearnessAllowance->edited_by = auth()->id();

        try {
            $dearnessAllowance->save();

            $dearnessAllowance->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Dearness Allowance Rate Updated!', 'data' => $dearnessAllowance]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
