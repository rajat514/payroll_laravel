<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayMatrixLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayMatrixLevelController extends Controller
{
    function index()
    {
        // $page = request('page') ? (int)request('page') : 1;
        // $limit = request('limit') ? (int)request('limit') : 30;
        // $offset = ($page - 1) * $limit;

        $query = PayMatrixLevel::with('payMatrixCell', 'payCommission');

        // $query->when(
        //     request('pay_commission_id'),
        //     fn($q) => $q->where('pay_commission_id', 'LIKE', '%' . request('pay_commission_id') . '%')
        // );

        $total_count = $query->count();

        $data = $query->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function levelByCommission($id)
    {
        $query = PayMatrixLevel::with('payMatrixCell', 'payCommission');

        $query->where('pay_commission_id', $id);

        $total_count = $query->count();

        $data = $query->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function show($id)
    {
        $data = PayMatrixLevel::with('addedBy', 'editedBy', 'history.addedBy', 'history.editedBy')->find($id);

        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'regex:/^(?:\d{1,2}[A-Z]|\d+)$/', 'unique:pay_matrix_levels'],
            'pay_commission_id' => 'required|numeric|exists:pay_commissions,id',
            'description' => 'nullable|string|max:191',

        ]);

        $payMatrixLevel = new PayMatrixLevel();
        $payMatrixLevel->name = $request['name'];
        $payMatrixLevel->description = $request['description'];
        $payMatrixLevel->pay_commission_id = $request['pay_commission_id'];
        $payMatrixLevel->added_by = auth()->id();

        try {
            $payMatrixLevel->save();

            return response()->json(['successMsg' => 'Pay Matrix Level Created!', 'data' => $payMatrixLevel]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $payMatrixLevel = PayMatrixLevel::find($id);
        if (!$payMatrixLevel) return response()->json(['errorMsg' => 'Pay Matrix Level not found!']);

        $request->validate([
            'name' => ['required', 'string', 'regex:/^(?:\d{1,2}[A-Z]|\d+)$/', "unique:pay_matrix_levels,name,$id,id"],
            'description' => 'nullable|string|max:191',
            'pay_commission_id' => 'required|numeric|exists:pay_commissions,id',
        ]);

        DB::beginTransaction();

        $old_data = $payMatrixLevel->toArray();

        $payMatrixLevel->name = $request['name'];
        $payMatrixLevel->description = $request['description'];
        $payMatrixLevel->pay_commission_id = $request['pay_commission_id'];
        $payMatrixLevel->edited_by = auth()->id();

        try {
            $payMatrixLevel->save();

            $payMatrixLevel->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Pay Matrix Level Updated!', 'data' => $payMatrixLevel]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
