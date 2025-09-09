<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HouseRentAllowanceRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HouseRentAllowanceRateController extends Controller
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

        $query = HouseRentAllowanceRate::query();

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
            'city_class' => 'required|in:X,Y,Z',
            'rate_percentage' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'notification_ref' => 'nullable|string'
        ]);

        $isSmallDate = HouseRentAllowanceRate::where('effective_from', '>=', $request['effective_from'])->get()->first();
        if ($isSmallDate) return response()->json(['errorMsg' => 'Effective From date is smaller than previous!'], 400);

        $hra = HouseRentAllowanceRate::where('city_class', $request['city_class'])->get()->last();

        DB::beginTransaction();

        if ($hra) {
            $hra->effective_till = \Carbon\Carbon::parse($request['effective_from'])->subDay()->format('Y-m-d');

            try {
                $hra->save();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['errorMsg' => $e->getMessage()], 500);
            }
        }

        $houseRentAllowance = new HouseRentAllowanceRate();
        $houseRentAllowance->city_class = $request['city_class'];
        $houseRentAllowance->rate_percentage = $request['rate_percentage'];
        $houseRentAllowance->effective_from = $request['effective_from'];
        $houseRentAllowance->effective_till = $request['effective_till'];
        $houseRentAllowance->notification_ref = $request['notification_ref'];
        $houseRentAllowance->added_by = auth()->id();

        try {
            $houseRentAllowance->save();
            DB::commit();
            return response()->json(['successMsg' => 'House Rent Allowance Rate Created!', 'data' => $houseRentAllowance]);
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

        $houseRentAllowance = HouseRentAllowanceRate::find($id);
        if (!$houseRentAllowance) return response()->json(['errorMsg' => 'House Rent Allowance Rate not found!'], 404);

        $request->validate([
            'city_class' => 'required|in:X,Y,Z',
            'rate_percentage' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'notification_ref' => 'nullable|string'
        ]);

        DB::beginTransaction();

        $old_data = $houseRentAllowance->toArray();

        $houseRentAllowance->city_class = $request['city_class'];
        $houseRentAllowance->rate_percentage = $request['rate_percentage'];
        $houseRentAllowance->effective_from = $request['effective_from'];
        $houseRentAllowance->effective_till = $request['effective_till'];
        $houseRentAllowance->notification_ref = $request['notification_ref'];
        $houseRentAllowance->edited_by = auth()->id();

        try {
            $houseRentAllowance->save();

            $houseRentAllowance->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'House Rent Allowance Rate Updated!', 'data' => $houseRentAllowance]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function show($id)
    {
        $data = HouseRentAllowanceRate::with('history.addedBy.roles:id,name', 'history.editedBy.roles:id,name', 'addedBy.roles:id,name', 'editedBy.roles:id,name')->find($id);

        return response()->json(['data' => $data]);
    }
}
