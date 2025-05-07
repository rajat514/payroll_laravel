<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayMatrixCell;
use Illuminate\Http\Request;

class PayMatrixCellController extends Controller
{
    function index()
    {
        $data = PayMatrixCell::with('payMatrixLevel')->get();

        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        $request->validate([
            'matrix_level_id' => 'required|numeric|exists:pay_matrix_levels,id',
            'index' => 'required|numeric',
            'amount' => 'required|numeric'
        ]);

        $payMatrixCell = new PayMatrixCell();
        $payMatrixCell->matrix_level_id = $request['matrix_level_id'];
        $payMatrixCell->index = $request['index'];
        $payMatrixCell->amount = $request['amount'];

        try {
            $payMatrixCell->save();

            return response()->json(['successMsg' => 'Pay Matrix Cell Created!', 'data' => $payMatrixCell]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $payMatrixCell = PayMatrixCell::find($id);
        if (!$payMatrixCell) return response()->json(['errorMsg' => 'Pay Matrix Cell not found!']);

        $request->validate([
            'matrix_level_id' => 'required|numeric|exists:pay_matrix_levels,id',
            'index' => 'required|numeric',
            'amount' => 'required|numeric'
        ]);

        $payMatrixCell->matrix_level_id = $request['matrix_level_id'];
        $payMatrixCell->index = $request['index'];
        $payMatrixCell->amount = $request['amount'];
        try {
            $payMatrixCell->save();

            return response()->json(['successMsg' => 'Pay Matrix Cell Updated!', 'data' => $payMatrixCell]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
