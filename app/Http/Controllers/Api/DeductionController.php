<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\EmployeePayStructure;
use App\Models\GISEligibility;
use App\Models\NetSalary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeductionController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = Deduction::query();

        $query->when(
            request('net_salary_id'),
            fn($q) => $q->where('net_salary_id', 'LIKE', '%' . request('net_salary_id') . '%')
        );

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'ASC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function show($id)
    {
        $data = Deduction::with('addedBy', 'editedBy', 'history.editedBy')->find($id);

        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        $request->validate([
            'net_salary_id' => 'required|numeric|exists:net_salaries,id',
            'income_tax' => 'nullable|numeric|',
            'professional_tax' => 'nullable|numeric',
            'license_fee' => 'nullable|numeric',
            'nfch_donation' => 'nullable|numeric',
            'gpf' => 'nullable|numeric',
            'transport_allowance_recovery' => 'nullable|numeric',
            'hra_recovery' => 'nullable|numeric',
            'computer_advance' => 'nullable|numeric',
            'computer_advance_installment' => 'nullable|numeric',
            'computer_advance_inst_no' => 'nullable|numeric',
            'computer_advance_balance' => 'nullable|numeric',
            'employee_contribution_10' => 'nullable|numeric',
            'govt_contribution_14_recovery' => 'nullable|numeric',
            'dies_non_recovery' => 'nullable|numeric',
            'computer_advance_interest' => 'nullable|numeric',
            'pay_recovery' => 'nullable|numeric',
            'nps_recovery' => 'nullable|numeric',
            'lic' => 'nullable|numeric',
            'credit_society_membership' => 'nullable|numeric',
        ]);

        $gisAmount = 0;

        $isAlreadyDeduction = Deduction::where('net_salary_id', $request['net_salary_id'])->get()->first();
        if ($isAlreadyDeduction) {
            return response()->json(['errorMsg' => 'This salary deduction has already deducted!'], 400);
        }

        $netSalary = NetSalary::find($request['net_salary_id']);
        if (!$netSalary) {
            return response()->json(['errorMsg' => 'Net Salary not found!'], 404);
        }

        $employee = Employee::find($netSalary->employee_id);
        if (!$employee) {
            return response()->json(['errorMsg' => 'Employee not found!'], 404);
        }
        $employeePayStructure = EmployeePayStructure::with('payMatrixCell.payMatrixLevel')->find($netSalary->employee_id);
        if (!$employeePayStructure) {
            return response()->json(['errorMsg' => 'Employee Pay Structure not found!'], 404);
        }

        $employeePayCell = $employeePayStructure->payMatrixCell;
        $employeePayLevel = $employeePayStructure->payMatrixCell->payMatrixLevel;

        $employeeGIS = GISEligibility::where('pay_matrix_level', $employeePayLevel->name)->get()->first();
        if (!$employeeGIS) {
            return response()->json(['errorMsg' => 'Employee GIS not found!'], 404);
        }
        if ($employee->gis_eligibility) {
            $gisAmount = $employeeGIS->amount;
        }

        if ($employee->credit_society_member) {
            if (!$request['credit_society_membership']) {
                return response()->json(['errorMsg' => 'Please fill the credit society membership!']);
            }
        }



        $totalDeduction = $request['income_tax'] + $request['professional_tax'] + $request['licence_fee'] + $request['nfch_donation'] + $request['gpf'] + $request['hra_recovery']
            + $request['computer_advance'] + $request['computer_advance_installment'] + $request['computer_advance_inst_no'] + $request['computer_advance_balance'] + $request['employee_contribution_10']
            + $request['govt_contribution_14_recovery'] + $request['dies_non_recovery'] + $request['computer_advance_interest'] + $gisAmount + $request['pay_recovery'] + $request['nps_recovery']
            + $request['lic'] + $request['credit_society_membership'];

        $deduction = new Deduction();
        $deduction->net_salary_id = $request['net_salary_id'];
        $deduction->income_tax = $request['income_tax'] ?? 0;
        $deduction->professional_tax = $request['professional_tax'] ?? 0;
        $deduction->license_fee = $request['license_fee'] ?? 0;
        $deduction->nfch_donation = $request['nfch_donation'] ?? 0;
        $deduction->gpf = $request['gpf'] ?? 0;
        $deduction->transport_allowance_recovery = $request['transport_allowance_recovery'] ?? 0;
        $deduction->hra_recovery = $request['npa_recovery'] ?? 0;
        $deduction->computer_advance = $request['computer_advance'] ?? 0;
        $deduction->computer_advance_installment = $request['computer_advance_installment'] ?? 0;
        $deduction->computer_advance_inst_no = $request['computer_advance_inst_no'] ?? 0;
        $deduction->computer_advance_balance = $request['computer_advance_balance'] ?? 0;
        $deduction->employee_contribution_10 = $request['employee_contribution_10'] ?? 0;
        $deduction->govt_contribution_14_recovery = $request['govt_contribution_14_recovery'] ?? 0;
        $deduction->dies_non_recovery = $request['dies_non_recovery'] ?? 0;
        $deduction->computer_advance_interest = $request['computer_advance_interest'] ?? 0;
        $deduction->gis = $gisAmount;
        $deduction->pay_recovery = $request['pay_recovery'] ?? 0;
        $deduction->nps_recovery = $request['nps_recovery'] ?? 0;
        $deduction->lic = $request['lic'] ?? 0;
        $deduction->credit_society = $request['credit_society_membership'] ?? 0;
        $deduction->total_deductions = $totalDeduction;
        $deduction->added_by = auth()->id();

        try {
            $deduction->save();

            $netSalary->net_amount = $netSalary->paySlip->total_pay - $totalDeduction;

            $netSalary->save();

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
            'income_tax' => 'nullable|numeric|',
            'professional_tax' => 'nullable|numeric',
            'license_fee' => 'nullable|numeric',
            'nfch_donation' => 'nullable|numeric',
            'gpf' => 'nullable|numeric',
            'transport_allowance_recovery' => 'nullable|numeric',
            'hra_recovery' => 'nullable|numeric',
            'computer_advance' => 'nullable|numeric',
            'computer_advance_installment' => 'nullable|numeric',
            'computer_advance_inst_no' => 'nullable|numeric',
            'computer_advance_balance' => 'nullable|numeric',
            'employee_contribution_10' => 'nullable|numeric',
            'govt_contribution_14_recovery' => 'nullable|numeric',
            'dies_non_recovery' => 'nullable|numeric',
            'computer_advance_interest' => 'nullable|numeric',
            'pay_recovery' => 'nullable|numeric',
            'nps_recovery' => 'nullable|numeric',
            'lic' => 'nullable|numeric',
            'credit_society_membership' => 'nullable|numeric',
        ]);

        $gisAmount = 0;

        $netSalary = NetSalary::find($request['net_salary_id']);
        if (!$netSalary) {
            return response()->json(['errorMsg' => 'Net Salary not found!'], 404);
        }

        $employee = Employee::find($netSalary->employee_id);
        if (!$employee) {
            return response()->json(['errorMsg' => 'Employee not found!'], 404);
        }
        $employeePayStructure = EmployeePayStructure::with('payMatrixCell.payMatrixLevel')->find($netSalary->employee_id);
        if (!$employeePayStructure) {
            return response()->json(['errorMsg' => 'Employee Pay Structure not found!'], 404);
        }

        $employeePayCell = $employeePayStructure->payMatrixCell;
        $employeePayLevel = $employeePayStructure->payMatrixCell->payMatrixLevel;

        $employeeGIS = GISEligibility::where('pay_matrix_level', $employeePayLevel->name)->get()->first();
        if (!$employeeGIS) {
            return response()->json(['errorMsg' => 'Employee GIS not found!'], 404);
        }
        if ($employee->gis_eligibility) {
            $gisAmount = $employeeGIS->amount;
        }

        if ($employee->credit_society_member) {
            if (!$request['credit_society_membership']) {
                return response()->json(['errorMsg' => 'Please fill the credit society membership!']);
            }
        }

        DB::beginTransaction();

        $old_data = $deduction->toArray();

        $deduction->net_salary_id = $request['net_salary_id'];
        $deduction->income_tax = $request['income_tax'];
        $deduction->professional_tax = $request['professional_tax'];
        $deduction->license_fee = $request['license_fee'];
        $deduction->nfch_donation = $request['nfch_donation'];
        $deduction->gpf = $request['gpf'];
        $deduction->hra_recovery = $request['hra_recovery'];
        $deduction->transport_allowance_recovery = $request['transport_allowance_recovery'];
        $deduction->computer_advance = $request['computer_advance'];
        $deduction->computer_advance_installment = $request['computer_advance_installment'];
        $deduction->computer_advance_inst_no = $request['computer_advance_inst_no'];
        $deduction->computer_advance_balance = $request['computer_advance_balance'];
        $deduction->employee_contribution_10 = $request['employee_contribution_10'];
        $deduction->govt_contribution_14_recovery = $request['govt_contribution_14_recovery'];
        $deduction->dies_non_recovery = $request['dies_non_recovery'];
        $deduction->computer_advance_interest = $request['computer_advance_interest'];
        $deduction->gis = $gisAmount;
        $deduction->pay_recovery = $request['pay_recovery'];
        $deduction->nps_recovery = $request['nps_recovery'];
        $deduction->lic = $request['lic'];
        $deduction->credit_society = $request['credit_society_membership'];
        // $request['income_tax'] ? $deduction->income_tax = $request['income_tax'] : $deduction->income_tax;
        // $request['professional_tax'] ? $deduction->professional_tax = $request['professional_tax'] : $deduction->professional_tax;
        // $request['license_fee'] ? $deduction->license_fee = $request['license_fee'] : $deduction->license_fee;
        // $request['nfch_donation'] ? $deduction->nfch_donation = $request['nfch_donation'] : $deduction->nfch_donation;
        // $request['gpf'] ? $deduction->gpf = $request['gpf'] : $deduction->gpf;
        // $request['hra_recovery'] ? $deduction->hra_recovery = $request['hra_recovery'] : $deduction->hra_recovery;
        // $request['transport_allowance_recovery'] ? $deduction->transport_allowance_recovery = $request['transport_allowance_recovery'] : $deduction->transport_allowance_recovery;
        // $request['computer_advance'] ? $deduction->computer_advance = $request['computer_advance'] : $deduction->computer_advance;
        // $request['computer_advance_installment'] ? $deduction->computer_advance_installment = $request['computer_advance_installment'] : $deduction->computer_advance_installment;
        // $request['computer_advance_inst_no'] ? $deduction->computer_advance_inst_no = $request['computer_advance_inst_no'] : $deduction->computer_advance_inst_no;
        // $request['computer_advance_balance'] ? $deduction->computer_advance_balance = $request['computer_advance_balance'] : $deduction->computer_advance_balance;
        // $request['employee_contribution_10'] ? $deduction->employee_contribution_10 = $request['employee_contribution_10'] : $deduction->employee_contribution_10;
        // $request['govt_contribution_14_recovery'] ? $deduction->govt_contribution_14_recovery = $request['govt_contribution_14_recovery'] : $deduction->govt_contribution_14_recovery;
        // $request['dies_non_recovery'] ? $deduction->dies_non_recovery = $request['dies_non_recovery'] : $deduction->dies_non_recovery;
        // $request['computer_advance_interest'] ? $deduction->computer_advance_interest = $request['computer_advance_interest'] : $deduction->computer_advance_interest;
        // $deduction->gis = $gisAmount;
        // $request['pay_recovery'] ? $deduction->pay_recovery = $request['pay_recovery'] : $deduction->pay_recovery;
        // $request['nps_recovery'] ? $deduction->nps_recovery = $request['nps_recovery'] : $deduction->nps_recovery;
        // $request['lic'] ? $deduction->lic = $request['lic'] : $deduction->lic;
        // $request['credit_society_membership'] ? $deduction->credit_society = $request['credit_society_membership'] : $deduction->credit_society;


        $totalDeduction = $deduction->income_tax + $deduction->professional_tax + $deduction->licence_fee + $deduction->nfch_donation + $deduction->gpf + $deduction->hra_amount
            + $deduction->computer_advance + $deduction->computer_advance_installment + $deduction->computer_advance_inst_no + $deduction->computer_advance_balance + $deduction->employee_contribution_10
            + $deduction->govt_contribution_14_recovery + $deduction->dies_non_recovery + $deduction->computer_advance_interest + $deduction->gis + $deduction->pay_recovery + $deduction->nps_recovery
            + $deduction->lic + $deduction->credit_society;

        $deduction->total_deductions = $totalDeduction;
        $deduction->edited_by = auth()->id();

        try {
            $deduction->save();

            $netSalary->net_amount = $netSalary->paySlip->total_pay - $deduction->total_deductions;

            $netSalary->save();

            $deduction->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Deduction updated!', 'data' => $deduction]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
