<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UniformAllowanceRate;
use Illuminate\Http\Request;

class UniformAllowanceRateController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = UniformAllowanceRate::query();

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        $request->validate([
            'applicable_post' => 'required|string',
            'amount' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'notification_ref' => 'nullable|string'
        ]);

        $uniformAllowance = new UniformAllowanceRate();
        $uniformAllowance->applicable_post = $request['applicable_post'];
        $uniformAllowance->amount = $request['amount'];
        $uniformAllowance->effective_from = $request['effective_from'];
        $uniformAllowance->effective_till = $request['effective_till'];
        $uniformAllowance->notification_ref = $request['notification_ref'];
        $uniformAllowance->added_by = auth()->id();

        try {
            $uniformAllowance->save();

            return response()->json(['successMsg' => 'Uniform Allowance Rate Created!', 'data' => $uniformAllowance]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $uniformAllowance = UniformAllowanceRate::find($id);
        if (!$uniformAllowance) return response()->json(['errorMsg' => 'Uniform Allowance Rate not found!'], 404);

        $request->validate([
            'applicable_post' => 'required|string',
            'amount' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'notification_ref' => 'nullable|string'
        ]);

        $uniformAllowance->applicable_post = $request['applicable_post'];
        $uniformAllowance->amount = $request['amount'];
        $uniformAllowance->effective_from = $request['effective_from'];
        $uniformAllowance->effective_till = $request['effective_till'];
        $uniformAllowance->notification_ref = $request['notification_ref'];
        $uniformAllowance->edited_by = auth()->id();

        try {
            $uniformAllowance->save();

            return response()->json(['successMsg' => 'Uniform Allowance Rate Created!', 'data' => $uniformAllowance]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
