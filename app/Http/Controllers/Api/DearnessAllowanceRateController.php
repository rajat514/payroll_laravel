<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DearnesAllowanceRate;
use Illuminate\Http\Request;

class DearnessAllowanceRateController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = DearnesAllowanceRate::query();

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        $request->validate([
            'rate_percentage' => 'required|numeric',
            'pwd_rate_percentage' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date',
            'notification_ref' => 'nullable|string'
        ]);

        $dearnessAllowance = new DearnesAllowanceRate();
        $dearnessAllowance->rate_percentage = $request['rate_percentage'];
        $dearnessAllowance->pwd_rate_percentage = $request['pwd_rate_percentage'];
        $dearnessAllowance->effective_from = $request['effective_from'];
        $dearnessAllowance->effective_till = $request['effective_till'];
        $dearnessAllowance->notification_ref = $request['notification_ref'];
        $dearnessAllowance->added_by = auth()->id();

        try {
            $dearnessAllowance->save();

            return response()->json(['successMsg' => 'Dearness Allowance Rate Created!', 'data' => $dearnessAllowance]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $dearnessAllowance = DearnesAllowanceRate::find($id);
        if (!$dearnessAllowance) return response()->json(['errorMsg' => 'Dearness Allowance Rate not found!'], 404);

        $request->validate([
            'rate_percentage' => 'required|numeric',
            'pwd_rate_percentage' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date',
            'notification_ref' => 'nullable|string'
        ]);

        $dearnessAllowance->rate_percentage = $request['rate_percentage'];
        $dearnessAllowance->pwd_rate_percentage = $request['pwd_rate_percentage'];
        $dearnessAllowance->effective_from = $request['effective_from'];
        $dearnessAllowance->effective_till = $request['effective_till'];
        $dearnessAllowance->notification_ref = $request['notification_ref'];
        $dearnessAllowance->edited_by = auth()->id();

        try {
            $dearnessAllowance->save();

            return response()->json(['successMsg' => 'Dearness Allowance Rate Updated!', 'data' => $dearnessAllowance]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
