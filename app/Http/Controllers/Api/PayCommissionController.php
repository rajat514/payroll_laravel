<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayCommission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayCommissionController extends Controller
{
    function index()
    {
        $data = PayCommission::with('payMatrixLevel.payMatrixCell')->get();

        return response()->json(['data' => $data]);
    }

    function show($id)
    {
        $data = PayCommission::with('addedBy', 'editedBy', 'history.addedBy', 'history.editedBy')->find($id);

        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'year' => 'required|numeric|digits:4',
            'is_active' => 'boolean|in:1,0'
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
        $payCommission = PayCommission::find($id);
        if (!$payCommission) return response()->json(['errorMsg' => 'Pay Commission not found!'], 404);

        $request->validate([
            'name' => 'required|string|max:191',
            'year' => 'required|numeric|digits:4',
            'is_active' => 'boolean|in:1,0'
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
