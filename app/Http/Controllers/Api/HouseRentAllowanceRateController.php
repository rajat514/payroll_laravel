<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HouseRentAllowanceRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HouseRentAllowanceRateController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = HouseRentAllowanceRate::query();

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        $request->validate([
            'city_class' => 'required|in:X,Y,Z',
            'rate_percentage' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'notification_ref' => 'nullable|string'
        ]);

        $isSmallDate = HouseRentAllowanceRate::where('effective_from', '>=', $request['effective_from'])->get()->first();
        if ($isSmallDate) return response()->json(['errorMsg' => 'Effective From date is smaller than previous!'], 400);

        $houseRentAllowance = new HouseRentAllowanceRate();
        $houseRentAllowance->city_class = $request['city_class'];
        $houseRentAllowance->rate_percentage = $request['rate_percentage'];
        $houseRentAllowance->effective_from = $request['effective_from'];
        $houseRentAllowance->effective_till = $request['effective_till'];
        $houseRentAllowance->notification_ref = $request['notification_ref'];
        $houseRentAllowance->added_by = auth()->id();

        try {
            $houseRentAllowance->save();

            return response()->json(['successMsg' => 'House Rent Allowance Rate Created!', 'data' => $houseRentAllowance]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $houseRentAllowance = HouseRentAllowanceRate::find($id);
        if (!$houseRentAllowance) return response()->json(['errorMsg' => 'House Rent Allowance Rate not found!'], 404);

        $request->validate([
            'city_class' => 'required|in:X,Y,Z',
            'rate_percentage' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'notification_ref' => 'nullable|string'
        ]);

        $isSmallDate = HouseRentAllowanceRate::where('effective_from', '>', $request['effective_from'])->get()->first();
        if ($isSmallDate) return response()->json(['errorMsg' => 'Effective From date is smaller than previous!'], 400);


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
        $data = HouseRentAllowanceRate::with('addedBy', 'editedBy', 'history.addedBy', 'history.editedBy')->find($id);

        return response()->json(['data' => $data]);
    }
}
