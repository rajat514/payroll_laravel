<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deduction;
use App\Models\DeductionRecoveries;
use App\Models\DeductionRecoveryClone;
use App\Models\DeductionRecoveryClones;
use App\Models\Employee;
use App\Models\EmployeePayStructure;
use App\Models\GISEligibility;
use App\Models\NetSalary;
use App\Models\PaySlipClone;
use App\Models\SalaryArrearClone;
use App\Models\SalaryArrears;
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
            fn($q) => $q->where('net_salary_id', request('net_salary_id'))
        );

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function show($id)
    {
        $data = Deduction::with('history.addedBy.roles:id,name', 'history.editedBy.roles:id,name', 'addedBy.roles:id,name', 'editedBy.roles:id,name')->find($id);

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
            'remarks' => 'nullable|string',
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
        $employeePayStructure = EmployeePayStructure::with('payMatrixCell.payMatrixLevel')->find($netSalary->paySlip->pay_structure_id);
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
        // return response()->json(['errorMsg' => 'Employee GIS not found!', 'data' => $gisAmount]);

        if ($employee->credit_society_member) {
            if (!$request['credit_society_membership']) {
                return response()->json(['errorMsg' => 'Please fill the credit society membership!']);
            }
        }




        $totalDeduction = $request['income_tax'] +
            $request['professional_tax'] +
            $request['license_fee'] +
            $request['nfch_donation'] +
            $request['gpf'] +
            $request['transport_allowance_recovery'] +
            $request['hra_recovery'] +
            $request['computer_advance'] +
            $request['computer_advance_installment'] +
            $request['computer_advance_inst_no'] +
            $request['computer_advance_balance'] +
            $request['employee_contribution_10'] +
            $request['govt_contribution_14_recovery'] +
            $request['dies_non_recovery'] +
            $request['computer_advance_interest'] +
            $gisAmount +
            $request['pay_recovery'] +
            $request['nps_recovery'] +
            $request['lic'] +
            $request['credit_society_membership'];

        $deduction = new Deduction();
        $deduction->net_salary_id = $request['net_salary_id'];
        $deduction->income_tax = $request['income_tax'] ?? 0;
        $deduction->professional_tax = $request['professional_tax'] ?? 0;
        $deduction->license_fee = $request['license_fee'] ?? 0;
        $deduction->nfch_donation = $request['nfch_donation'] ?? 0;
        $deduction->gpf = $request['gpf'] ?? 0;
        $deduction->transport_allowance_recovery = $request['transport_allowance_recovery'] ?? 0;
        $deduction->hra_recovery = $request['hra_recovery'] ?? 0;
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
            'gis' => 'nullable|numeric',
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
            'remarks' => 'nullable|string',
            'total_deductions' => 'required|numeric',
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
        $employeePayStructure = EmployeePayStructure::with('payMatrixCell.payMatrixLevel')->find($netSalary->paySlip->pay_structure_id);
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

        $net_salary_old_data = $netSalary->toArray();
        $paySlip = $netSalary->paySlip;

        $net_salary_old_data['pay_structure_id'] = $paySlip->pay_structure_id;
        $net_salary_old_data['basic_pay'] = $paySlip->basic_pay;
        $net_salary_old_data['da_rate_id'] = $paySlip->da_rate_id;
        $net_salary_old_data['da_amount'] = $paySlip->da_amount;
        $net_salary_old_data['hra_rate_id'] = $paySlip->hra_rate_id;
        $net_salary_old_data['hra_amount'] = $paySlip->hra_amount;
        $net_salary_old_data['npa_rate_id'] = $paySlip->npa_rate_id;
        $net_salary_old_data['npa_amount'] = $paySlip->npa_amount;
        $net_salary_old_data['transport_rate_id'] = $paySlip->transport_rate_id;
        $net_salary_old_data['transport_amount'] = $paySlip->transport_amount;
        $net_salary_old_data['uniform_rate_id'] = $paySlip->uniform_rate_id;
        $net_salary_old_data['uniform_rate_amount'] = $paySlip->uniform_rate_amount;
        $net_salary_old_data['pay_plus_npa'] = $paySlip->pay_plus_npa;
        $net_salary_old_data['govt_contribution'] = $paySlip->govt_contribution;
        $net_salary_old_data['da_on_ta'] = $paySlip->da_on_ta;
        $net_salary_old_data['arrears'] = $paySlip->arrears;
        $net_salary_old_data['spacial_pay'] = $paySlip->spacial_pay;
        $net_salary_old_data['da_1'] = $paySlip->da_1;
        $net_salary_old_data['da_2'] = $paySlip->da_2;
        $net_salary_old_data['itc_leave_salary'] = $paySlip->itc_leave_salary;
        $net_salary_old_data['total_pay'] = $paySlip->total_pay;
        $net_salary_old_data['income_tax'] = $deduction->income_tax;
        $net_salary_old_data['professional_tax'] = $deduction->professional_tax;
        $net_salary_old_data['license_fee'] = $deduction->license_fee;
        $net_salary_old_data['nfch_donation'] = $deduction->nfch_donation;
        $net_salary_old_data['gpf'] = $deduction->gpf;
        $net_salary_old_data['transport_allowance_recovery'] = $deduction->transport_allowance_recovery;
        $net_salary_old_data['hra_recovery'] = $deduction->hra_recovery;
        $net_salary_old_data['computer_advance'] = $deduction->computer_advance;
        $net_salary_old_data['computer_advance_installment'] = $deduction->computer_advance_installment;
        $net_salary_old_data['computer_advance_inst_no'] = $deduction->computer_advance_inst_no;
        $net_salary_old_data['computer_advance_balance'] = $deduction->computer_advance_balance;
        $net_salary_old_data['employee_contribution_10'] = $deduction->employee_contribution_10;
        $net_salary_old_data['govt_contribution_14_recovery'] = $deduction->govt_contribution_14_recovery;
        $net_salary_old_data['dies_non_recovery'] = $deduction->dies_non_recovery;
        $net_salary_old_data['computer_advance_interest'] = $deduction->computer_advance_interest;
        $net_salary_old_data['gis'] = $deduction->gis;
        $net_salary_old_data['pay_recovery'] = $deduction->pay_recovery;
        $net_salary_old_data['nps_recovery'] = $deduction->nps_recovery;
        $net_salary_old_data['lic'] = $deduction->lic;
        $net_salary_old_data['credit_society'] = $deduction->credit_society;
        $net_salary_old_data['total_deductions'] = $deduction->total_deductions;
        $net_salary_old_data['salary_arrears'] = json_encode($paySlip->salaryArrears); // Assuming relation or array
        $net_salary_old_data['deduction_recoveries'] = json_encode($deduction->deductionRecoveries);


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
        $deduction->gis = $request['gis'];
        $deduction->pay_recovery = $request['pay_recovery'];
        $deduction->nps_recovery = $request['nps_recovery'];
        $deduction->lic = $request['lic'];
        $deduction->credit_society = $request['credit_society_membership'];
        $deduction->total_deductions = $request['total_deductions'];

        // $deductionRecoverySum = DeductionRecoveries::where('deduction_id', $deduction->id)->sum('amount');
        $deductionRecoveryTotal = 0;
        if ($request->filled('deduction_recoveries')) {
            foreach ($request->deduction_recoveries as $item) {
                $deductionRecoveryTotal += $item['amount'];
            }
        }

        $totalDeduction = $deduction->income_tax +
            $deduction->professional_tax +
            $deduction->license_fee +
            $deduction->nfch_donation +
            $deduction->gpf +
            $deduction->hra_recovery +
            $deduction->transport_allowance_recovery +
            $deduction->computer_advance +
            $deduction->computer_advance_installment +
            $deduction->computer_advance_inst_no +
            $deduction->computer_advance_balance +
            $deduction->employee_contribution_10 +
            $deduction->govt_contribution_14_recovery +
            $deduction->dies_non_recovery +
            $deduction->computer_advance_interest +
            $deduction->gis +
            $deduction->pay_recovery +
            $deduction->nps_recovery  +
            $deduction->lic +
            $deduction->credit_society +
            $deductionRecoveryTotal;

        // $deduction->total_deductions = $totalDeduction;
        $deduction->edited_by = auth()->id();

        try {
            $deduction->save();

            $netSalary->net_amount = $netSalary->paySlip->total_pay - $deduction->total_deductions;
            $netSalary->edited_by = auth()->id();
            $netSalary->remarks = $request['remarks'];

            $netSalary->save();

            $net_salary_clone = $netSalary->history()->create($net_salary_old_data);
            // $old_data['net_salary_clone_id'] = $net_salary_clone->id;

            // $deductionClone = $deduction->history()->create($old_data);

            // $old_pay_slip_data = $netSalary->paySlip->toArray();
            // $old_pay_slip_data['pay_slip_id'] = $netSalary->paySlip->id;
            // $old_pay_slip_data['net_salary_clone_id'] = $net_salary_clone->id;
            // $paySlipClone = PaySlipClone::create($old_pay_slip_data);

            // $old_arrears = SalaryArrears::where('pay_slip_id', $netSalary->paySlip->id)->get()->toArray();
            // foreach ($old_arrears as $old_arrear) {
            //     unset($old_arrear['id']); // remove ID to avoid duplication
            //     $old_arrear['net_salary_clone_id'] = $net_salary_clone->id;
            //     $old_arrear['pay_slip_id'] = $netSalary->paySlip->id;
            //     $old_arrear['pay_slip_clone_id'] = $paySlipClone->id;

            //     // If you have history() relation (recommended)
            //     SalaryArrearClone::create($old_arrear);

            //     // OR, if you're saving in a separate `salary_arrear_histories` table manually
            //     // SalaryArrearHistory::create($old_arrear);
            // }

            // $old_recoveries = DeductionRecoveries::where('deduction_id', $deduction->id)->get()->toArray();
            // foreach ($old_recoveries as $old_recovery) {
            //     unset($old_recovery['id']); // remove ID to avoid duplication
            //     $old_recovery['net_salary_clone_id'] = $net_salary_clone->id;
            //     $old_recovery['duduction_id'] = $deduction->id;
            //     $old_recovery['deduction_clone_id'] = $deductionClone->id;

            //     // If you have history() relation (recommended)
            //     DeductionRecoveryClones::create($old_recovery);

            //     // OR, if you're saving in a separate `salary_arrear_histories` table manually
            //     // SalaryArrearHistory::create($old_arrear);
            // }

            // Delete previous arrears
            DeductionRecoveries::where('deduction_id', $deduction->id)->delete();

            // Recreate from new request data
            if ($request->filled('deduction_recoveries')) {
                foreach ($request->deduction_recoveries as $item) {
                    DeductionRecoveries::create([
                        'deduction_id' => $deduction->id,
                        'type' => $item['type'],
                        'amount' => $item['amount'],
                        'added_by' => auth()->id(),
                    ]);
                }
            }

            DB::commit();
            return response()->json(['successMsg' => 'Deduction updated!', 'data' => $deduction]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
