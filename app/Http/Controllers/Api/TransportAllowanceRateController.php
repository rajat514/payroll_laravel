<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransportAllowanceRate;
use Illuminate\Http\Request;

class TransportAllowanceRateController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = TransportAllowanceRate::query();

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        $request->validate([
            'city_class' => 'required|in:X,Y,Z',
            'pwd_applicable' => 'boolean|in:1,0',
            'transport_type' => 'required|in:Type1,Type2,Type3,Type4',
            'amount' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date',
            'notification_ref' => 'nullable|string'
        ]);

        $transportAllowance = new TransportAllowanceRate();
        $transportAllowance->city_class = $request['city_class'];
        $transportAllowance->pwd_applicable = $request['pwd_applicable'];
        $transportAllowance->transport_type = $request['transport_type'];
        $transportAllowance->amount = $request['amount'];
        $transportAllowance->effective_from = $request['effective_from'];
        $transportAllowance->effective_till = $request['effective_till'];
        $transportAllowance->notification_ref = $request['notification_ref'];

        try {
            $transportAllowance->save();

            return response()->json(['successMsg' => 'Transport Allowance Rate Created!', 'data' => $transportAllowance]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $transportAllowance = TransportAllowanceRate::find($id);
        if (!$transportAllowance) return response()->json(['errorMsg' => 'Transport Allowance Rate not found!'], 404);

        $request->validate([
            'city_class' => 'required|in:X,Y,Z',
            'pwd_applicable' => 'boolean|in:1,0',
            'transport_type' => 'required|in:Type1,Type2,Type3,Type4',
            'amount' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date',
            'notification_ref' => 'nullable|string'
        ]);

        $transportAllowance->city_class = $request['city_class'];
        $transportAllowance->pwd_applicable = $request['pwd_applicable'];
        $transportAllowance->transport_type = $request['transport_type'];
        $transportAllowance->amount = $request['amount'];
        $transportAllowance->effective_from = $request['effective_from'];
        $transportAllowance->effective_till = $request['effective_till'];
        $transportAllowance->notification_ref = $request['notification_ref'];

        try {
            $transportAllowance->save();

            return response()->json(['successMsg' => 'Transport Allowance Rate Created!', 'data' => $transportAllowance]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
