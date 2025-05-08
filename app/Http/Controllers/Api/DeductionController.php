<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deduction;
use Illuminate\Http\Request;

class DeductionController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = Deduction::with('addby:id,name,role_id', 'editedby:id,name,role_id');

        $query->when(
            request('net_salary_id'),
            fn($q) => $q->where('net_salary_id', 'LIKE', '%' . request('net_salary_id') . '%')
        );

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'ASC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        $request->validate([
            'net_salary_id' => 'required|numeric|exists:net_salaries,id',
            'income_tax' => 'required|numeric|',
            'professional_tax' => 'required|numeric',
            'licence_fee' => 'required|numeric',
            'nfch_donation' => 'required|numeric',
            'gpf' => 'required|numeric',
            'transport_allowance_recovery' => 'required|numeric',
            'hra_recovery' => 'required|numeric',
            'computer_advance' => 'required|numeric',
            'computer_advance_installment' => 'required|numeric',
            'computer_advance_inst_no' => 'required|numeric',
            'computer_advance_balance' => 'required|numeric',
            'employee_contribution_10' => 'required|numeric',
            'govt_contribution_14_recovery' => 'required|numeric',
            'dies_non_recovery' => 'nullable|numeric',
            'computer_advance_interest' => 'required|numeric',
            'gis' => 'required|numeric',
            'pay_recovery' => 'required|numeric',
            'nps_recovery' => 'required|numeric',
            'lic' => 'required|numeric',
            'credit_society' => 'required|numeric',
            'total_deductions' => 'required|numeric',
        ]);

        $deduction = new Deduction();
        $deduction->net_salary_id = $request['net_salary_id'];
        $deduction->income_tax = $request['income_tax'];
        $deduction->professional_tax = $request['professional_tax'];
        $deduction->licence_fee = $request['licence_fee'];
        $deduction->nfch_donation = $request['nfch_donation'];
        $deduction->gpf = $request['gpf'];
        $deduction->transport_allowance_recovery = $request['hra_amount'];
        $deduction->hra_recovery = $request['npa_rate_id'];
        $deduction->computer_advance = $request['npa_amount'];
        $deduction->computer_advance_installment = $request['computer_advance_installment'];
        $deduction->computer_advance_inst_no = $request['computer_advance_inst_no'];
        $deduction->computer_advance_balance = $request['computer_advance_balance'];
        $deduction->employee_contribution_10 = $request['employee_contribution_10'];
        $deduction->govt_contribution_14_recovery = $request['govt_contribution_14_recovery'];
        $deduction->dies_non_recovery = $request['dies_non_recovery'];
        $deduction->computer_advance_interest = $request['computer_advance_interest'];
        $deduction->gis = $request['gis'];
        $deduction->pay_recovery = $request['pay_recovery'];
        $deduction->nps_recovery = $request['nps_recovery'];
        $deduction->lic = $request['lic'];
        $deduction->credit_society = $request['credit_society'];
        $deduction->total_deductions = $request['total_deductions'];
        $deduction->edited_by = auth()->id();

        try {
            $deduction->save();

            return response()->json(['successMsg' => 'Deduction created!', 'data' => $deduction]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $deduction = Deduction::find($id);
        if (!$deduction) return response()->json(['errorMsg' => 'Deduction not found!'], 404);

        $request->validate([
            'net_salary_id' => 'required|numeric|exists:net_salaries,id',
            'income_tax' => 'required|numeric|',
            'professional_tax' => 'required|numeric',
            'licence_fee' => 'required|numeric',
            'nfch_donation' => 'required|numeric',
            'gpf' => 'required|numeric',
            'transport_allowance_recovery' => 'required|numeric',
            'hra_recovery' => 'required|numeric',
            'computer_advance' => 'required|numeric',
            'computer_advance_installment' => 'required|numeric',
            'computer_advance_inst_no' => 'required|numeric',
            'computer_advance_balance' => 'required|numeric',
            'employee_contribution_10' => 'required|numeric',
            'govt_contribution_14_recovery' => 'required|numeric',
            'dies_non_recovery' => 'nullable|numeric',
            'computer_advance_interest' => 'required|numeric',
            'gis' => 'required|numeric',
            'pay_recovery' => 'required|numeric',
            'nps_recovery' => 'required|numeric',
            'lic' => 'required|numeric',
            'credit_society' => 'required|numeric',
            'total_deductions' => 'required|numeric',
        ]);

        $deduction->net_salary_id = $request['net_salary_id'];
        $deduction->income_tax = $request['income_tax'];
        $deduction->professional_tax = $request['professional_tax'];
        $deduction->licence_fee = $request['licence_fee'];
        $deduction->nfch_donation = $request['nfch_donation'];
        $deduction->gpf = $request['gpf'];
        $deduction->transport_allowance_recovery = $request['hra_amount'];
        $deduction->hra_recovery = $request['npa_rate_id'];
        $deduction->computer_advance = $request['npa_amount'];
        $deduction->computer_advance_installment = $request['computer_advance_installment'];
        $deduction->computer_advance_inst_no = $request['computer_advance_inst_no'];
        $deduction->computer_advance_balance = $request['computer_advance_balance'];
        $deduction->employee_contribution_10 = $request['employee_contribution_10'];
        $deduction->govt_contribution_14_recovery = $request['govt_contribution_14_recovery'];
        $deduction->dies_non_recovery = $request['dies_non_recovery'];
        $deduction->computer_advance_interest = $request['computer_advance_interest'];
        $deduction->gis = $request['gis'];
        $deduction->pay_recovery = $request['pay_recovery'];
        $deduction->nps_recovery = $request['nps_recovery'];
        $deduction->lic = $request['lic'];
        $deduction->credit_society = $request['credit_society'];
        $deduction->total_deductions = $request['total_deductions'];
        $deduction->edited_by = auth()->id();

        try {
            $deduction->save();

            return response()->json(['successMsg' => 'Deduction Updated!', 'data' => $deduction]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
