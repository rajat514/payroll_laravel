<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeTransportAllowance;
use App\Models\TransportAllowanceRate;
use Illuminate\Http\Request;

class TransportAllowanceRateController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = EmployeeTransportAllowance::query();

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        $request->validate([
            'pay_level' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        $transportAllowance = new EmployeeTransportAllowance();
        $transportAllowance->pay_level = $request['pay_level'];
        $transportAllowance->amount = $request['amount'];
        $transportAllowance->added_by = auth()->id();

        try {
            $transportAllowance->save();

            return response()->json(['successMsg' => 'Transport Allowance Rate Created!', 'data' => $transportAllowance]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {

        $transportAllowance = EmployeeTransportAllowance::find($id);
        if (!$transportAllowance) return response()->json(['errorMsg' => 'Transport Allowance Rate not found!'], 404);

        $request->validate([
            'pay_level' => 'required|string',
            'amount' => 'required|numeric',
        ]);


        $transportAllowance->pay_level = $request['pay_level'];
        $transportAllowance->amount = $request['amount'];
        $transportAllowance->added_by = auth()->id();
        $transportAllowance->edited_by = auth()->id();

        try {
            $transportAllowance->save();

            return response()->json(['successMsg' => 'Transport Allowance Rate Updated!', 'data' => $transportAllowance]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
