<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NetSalary;
use App\Models\PaySlip;
use Illuminate\Http\Request;

class PaySlipController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = PaySlip::with('addby:id,name,role_id', 'editby:id,name,role_id');

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
            'pay_structure_id' => 'required|numeric|exists:employee_pay_structures,id',
            'basic_pay' => 'required|numeric',
            'da_rate_id' => 'required|numeric|exists:dearness_allowance_rates,id',
            'da_amount' => 'required|numeric',
            'hra_rate_id' => 'required|numeric|exists:house_rent_allowance_rates,id',
            'hra_amount' => 'required|numeric',
            'npa_rate_id' => 'required|numeric|exists:non_practicing_allowance_rates,id',
            'npa_amount' => 'required|numeric',
            'transport_rate_id' => 'required|numeric|exists:transport_allowance_rates,id',
            'transport_amount' => 'required|numeric',
            'uniform_rate_id' => 'required|numeric|exists:uniform_allowance_rates,id',
            'uniform_rate_amount' => 'required|numeric',
            'pay_plus_npa' => 'required|numeric',
            'govt_contribution' => 'nullable|numeric',
            'da_on_ta' => 'required|numeric',
            'appears' => 'nullable|numeric',
            'spacial_pay' => 'nullable|numeric',
            'da_1' => 'nullable|numeric',
            'da_2' => 'nullable|numeric',
            'itc_leave_salary' => 'nullable|numeric',
            'total_pay' => 'nullable|numeric',
        ]);

        $salaryPay = new PaySlip();
        $salaryPay->net_salary_id = $request['net_salary_id'];
        $salaryPay->pay_structure_id = $request['pay_structure_id'];
        $salaryPay->basic_pay = $request['basic_pay'];
        $salaryPay->da_rate_id = $request['da_rate_id'];
        $salaryPay->da_amount = $request['da_amount'];
        $salaryPay->hra_rate_id = $request['hra_rate_id'];
        $salaryPay->hra_amount = $request['hra_amount'];
        $salaryPay->npa_rate_id = $request['npa_rate_id'];
        $salaryPay->npa_amount = $request['npa_amount'];
        $salaryPay->transport_rate_id = $request['transport_rate_id'];
        $salaryPay->transport_amount = $request['transport_amount'];
        $salaryPay->uniform_rate_id = $request['uniform_rate_id'];
        $salaryPay->uniform_rate_amount = $request['uniform_rate_amount'];
        $salaryPay->pay_plus_npa = $request['pay_plus_npa'];
        $salaryPay->govt_contribution = $request['govt_contribution'];
        $salaryPay->da_on_ta = $request['da_on_ta'];
        $salaryPay->appears = $request['appears'];
        $salaryPay->spacial_pay = $request['spacial_pay'];
        $salaryPay->da_1 = $request['da_1'];
        $salaryPay->da_2 = $request['da_2'];
        $salaryPay->itc_leave_salary = $request['itc_leave_salary'];
        $salaryPay->total_pay = $request['total_pay'];
        $salaryPay->edited_by = auth()->id();

        try {
            $salaryPay->save();

            return response()->json(['successMsg' => 'Pay Slip created!', 'data' => $salaryPay]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $salaryPay = PaySlip::find($id);
        if (!$salaryPay) return response()->json(['errorMsg' => 'Pay slip not found!'], 404);

        $request->validate([
            'net_salary_id' => 'required|numeric|exists:net_salaries,id',
            'pay_structure_id' => 'required|numeric|exists:employee_pay_structures,id',
            'basic_pay' => 'required|numeric',
            'da_rate_id' => 'required|numeric|exists:dearness_allowance_rates,id',
            'da_amount' => 'required|numeric',
            'hra_rate_id' => 'required|numeric|exists:house_rent_allowance_rates,id',
            'hra_amount' => 'required|numeric',
            'npa_rate_id' => 'required|numeric|exists:non_practicing_allowance_rates,id',
            'npa_amount' => 'required|numeric',
            'transport_rate_id' => 'required|numeric|exists:transport_allowance_rates,id',
            'transport_amount' => 'required|numeric',
            'uniform_rate_id' => 'required|numeric|exists:uniform_allowance_rates,id',
            'uniform_rate_amount' => 'required|numeric',
            'pay_plus_npa' => 'required|numeric',
            'govt_contribution' => 'nullable|numeric',
            'da_on_ta' => 'required|numeric',
            'appears' => 'nullable|numeric',
            'spacial_pay' => 'nullable|numeric',
            'da_1' => 'nullable|numeric',
            'da_2' => 'nullable|numeric',
            'itc_leave_salary' => 'nullable|numeric',
            'total_pay' => 'nullable|numeric',
        ]);

        $salaryPay->net_salary_id = $request['net_salary_id'];
        $salaryPay->pay_structure_id = $request['pay_structure_id'];
        $salaryPay->basic_pay = $request['basic_pay'];
        $salaryPay->da_rate_id = $request['da_rate_id'];
        $salaryPay->da_amount = $request['da_amount'];
        $salaryPay->hra_rate_id = $request['hra_rate_id'];
        $salaryPay->hra_amount = $request['hra_amount'];
        $salaryPay->npa_rate_id = $request['npa_rate_id'];
        $salaryPay->npa_amount = $request['npa_amount'];
        $salaryPay->transport_rate_id = $request['transport_rate_id'];
        $salaryPay->transport_amount = $request['transport_amount'];
        $salaryPay->uniform_rate_id = $request['uniform_rate_id'];
        $salaryPay->uniform_rate_amount = $request['uniform_rate_amount'];
        $salaryPay->pay_plus_npa = $request['pay_plus_npa'];
        $salaryPay->govt_contribution = $request['govt_contribution'];
        $salaryPay->da_on_ta = $request['da_on_ta'];
        $salaryPay->appears = $request['appears'];
        $salaryPay->spacial_pay = $request['spacial_pay'];
        $salaryPay->da_1 = $request['da_1'];
        $salaryPay->da_2 = $request['da_2'];
        $salaryPay->itc_leave_salary = $request['itc_leave_salary'];
        $salaryPay->edited_by = auth()->id();

        try {
            $salaryPay->save();

            return response()->json(['successMsg' => 'Pay slip Updated!', 'data' => $salaryPay]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
