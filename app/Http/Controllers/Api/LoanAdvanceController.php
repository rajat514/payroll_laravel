<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoanAdvance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanAdvanceController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = LoanAdvance::with('employee');

        $query->when(
            request('employee_id'),
            fn($q) => $q->where('employee_id', 'LIKE', '%' . request('employee_id') . '%')
        );

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|numeric|exists:employees,id',
            'loan_type' => 'required|in:Computer,Housing,Vehicle,Festival,Other',
            'loan_amount' => 'required|numeric',
            'interest_rate' => 'required|numeric',
            'sanctioned_date' => 'required|date',
            'total_installments' => 'required|numeric',
            'current_installment' => 'required|numeric',
            'remaining_balance' => 'required|numeric',
            'is_active' => 'boolean|in:1,0',
        ]);

        $loanAdvance = new LoanAdvance();
        $loanAdvance->employee_id = $request['employee_id'];
        $loanAdvance->loan_type = $request['loan_type'];
        $loanAdvance->loan_amount = $request['loan_amount'];
        $loanAdvance->interest_rate = $request['interest_rate'];
        $loanAdvance->sanctioned_date = $request['sanctioned_date'];
        $loanAdvance->total_installments = $request['total_installments'];
        $loanAdvance->current_installment = $request['current_installment'];
        $loanAdvance->remaining_balance = $request['remaining_balance'];
        $loanAdvance->is_active = $request['is_active'];
        $loanAdvance->added_by = auth()->id();

        try {
            $loanAdvance->save();

            return response()->json(['successMsg' => 'Employee Loan Created!', 'data' => $loanAdvance]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $loanAdvance = LoanAdvance::find($id);
        if (!$loanAdvance) return response()->json(['errorMsg' => 'Employee Loan not found!'], 404);

        $request->validate([
            'employee_id' => 'required|numeric|exists:employees,id',
            'loan_type' => 'required|in:Computer,Housing,Vehicle,Festival,Other',
            'loan_amount' => 'required|numeric',
            'interest_rate' => 'required|numeric',
            'sanctioned_date' => 'required|date',
            'total_installments' => 'required|numeric',
            'current_installment' => 'required|numeric',
            'remaining_balance' => 'required|numeric',
            'is_active' => 'boolean|in:1,0',
        ]);

        DB::beginTransaction();

        $old_data = $loanAdvance->toArray();

        $loanAdvance->employee_id = $request['employee_id'];
        $loanAdvance->loan_type = $request['loan_type'];
        $loanAdvance->loan_amount = $request['loan_amount'];
        $loanAdvance->interest_rate = $request['interest_rate'];
        $loanAdvance->sanctioned_date = $request['sanctioned_date'];
        $loanAdvance->total_installments = $request['total_installments'];
        $loanAdvance->current_installment = $request['current_installment'];
        $loanAdvance->remaining_balance = $request['remaining_balance'];
        $loanAdvance->is_active = $request['is_active'];
        $loanAdvance->edited_by = auth()->id();

        try {
            $loanAdvance->save();

            $loanAdvance->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Employee Loan Updated!', 'data' => $loanAdvance]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function show($id)
    {
        $data = LoanAdvance::with('addedBy', 'editedBy', 'history.addedBy', 'history.editedBy')->find($id);

        return response()->json(['data' => $data]);
    }
}
