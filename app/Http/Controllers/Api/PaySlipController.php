<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DearnesAllowanceRate;
use App\Models\Deduction;
use App\Models\DeductionClone;
use App\Models\DeductionRecoveries;
use App\Models\DeductionRecoveryClones;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeePayStructure;
use App\Models\EmployeeQuarter;
use App\Models\EmployeeStatus;
use App\Models\EmployeeTransportAllowance;
use App\Models\GISEligibility;
use App\Models\HouseRentAllowanceRate;
use App\Models\LoanAdvance;
use App\Models\NetSalary;
use App\Models\NonPracticingAllowanceRate;
use App\Models\PayMatrixCell;
use App\Models\PaySlip;
use App\Models\Quarter;
use App\Models\SalaryArrearClone;
use App\Models\SalaryArrears;
use App\Models\UniformAllowanceRate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaySlipController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = PaySlip::with('addedBy', 'editedBy', 'netSalary.employee');

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
        $data = PaySlip::with(
            'history.addedBy.roles:id,name',
            'history.editedBy.roles:id,name',
            'addedBy.roles:id,name',
            'editedBy.roles:id,name',
            'netSalary.employee',
            'history.netSalary',
            'history.netSalary.employee',
            'history.netSalary.deduction'
        )->find($id);

        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        $taAmount = 0; // Transport Allowance
        // $csmAmount = 0; // Credit society Member
        $npaAmount = 0; // Non Practicing Allowance
        $hraAmount = 0; // House Rent Allowance
        $daRateAmount = 0; // Dearness Allowance Rate
        $uaRateAmount = 0; // Uniform Allowance Rate 
        $totalBasicSalary = 0;
        $netAmount = 0;
        $payPlusNpa = 0;
        $govt_contribution_14 = 0;
        $employee_contribution_10 = 0;
        $gisAmount = 0;
        $license_fee = 0;
        // return response()->json($request->all());
        $request->validate([
            'employee_id' => 'required|numeric|exists:employees,id',
            'month' => 'required|numeric|max:12|min:1',
            'year' => 'required|numeric|digits:4|min:1900',
            'processing_date' => 'required|date',
            'payment_date' => 'nullable|date',
            'employee_bank_id' => 'required|numeric|exists:employee_bank_accounts,id',
            'net_amount' => 'required|numeric',
            'remarks' => 'nullable|string',

            'net_salary_id' => 'nullable|numeric|exists:net_salaries,id',
            'pay_structure_id' => 'required|numeric|exists:employee_pay_structures,id',
            'basic_pay' => 'required|numeric',
            'da_rate_id' => 'required|numeric|exists:dearnes_allowance_rates,id',
            'da_amount' => 'required|numeric',
            'hra_rate_id' => 'nullable|numeric|exists:house_rent_allowance_rates,id',
            'hra_amount' => 'nullable|numeric',
            'npa_rate_id' => 'nullable|numeric|exists:non_practicing_allowance_rates,id',
            'npa_amount' => 'nullable|numeric',
            'transport_rate_id' => 'nullable|numeric|exists:employee_transport_allowances,id',
            'transport_amount' => 'nullable|numeric',
            'uniform_rate_id' => 'nullable|numeric|exists:uniform_allowance_rates,id',
            'uniform_rate_amount' => 'nullable|numeric',
            'da_on_ta' => 'nullable|numeric',
            'arrears' => 'nullable|numeric',
            'spacial_pay' => 'nullable|numeric',
            'da_1' => 'nullable|numeric',
            'da_2' => 'nullable|numeric',
            'itc_leave_salary' => 'nullable|numeric',
            'total_pay' => 'required|numeric',

            'income_tax' => 'nullable|numeric|',
            'professional_tax' => 'nullable|numeric',
            'nfch_donation' => 'nullable|numeric',
            'gpf' => 'nullable|numeric',
            'transport_allowance_recovery' => 'nullable|numeric',
            'hra_recovery' => 'nullable|numeric',
            'license_fee' => 'nullable|numeric',
            'computer_advance_installment' => 'nullable|numeric',
            'computer_advance_balance' => 'nullable|numeric',
            'employee_contribution_10' => 'nullable|numeric',
            'govt_contribution_14_recovery' => 'nullable|numeric',
            'dies_non_recovery' => 'nullable|numeric',
            'computer_advance_interest' => 'nullable|numeric',
            'pay_recovery' => 'nullable|numeric',
            'nps_recovery' => 'nullable|numeric',
            'lic' => 'nullable|numeric',
            'credit_society_membership' => 'nullable|numeric',
            'total_deductions' => 'nullable|numeric',
            'gis' => 'nullable|numeric',

            'salary_arrears' => 'nullable|array',
            'salary_arrears.*.type' => 'required_with:salary_arrears|string',
            'salary_arrears.*.amount' => 'required_with:salary_arrears|numeric|min:0',

            'deduction_recoveries' => 'nullable|array',
            'deduction_recoveries.*.type' => 'required_with:deduction_recoveries|string',
            'deduction_recoveries.*.amount' => 'required_with:deduction_recoveries|numeric|min:0',
        ]);

        // $old_net_salary = NetSalary::where('employee_id', $request['employee_id'])->where('month', $request['month'])->where('year', $request['year'])->get()->first();
        // if ($old_net_salary) return response()->json(['errorMsg' => 'This month salary is already generated!'], 400);

        $employee = Employee::with(
            'employeeDesignation',
            'employeeStatus',
            'employeePayStructure.PayMatrixCell.payMatrixLevel',
            'employeeBank',
            'latestEmployeeDesignation'
        )->find($request['employee_id']);
        if (!$employee) return response()->json(['errorMsg' => 'Employee not found!'], 404);

        // $employeeStatus = EmployeeStatus::where('employee_id', $employee->id)->orderBy('effective_from', 'DESC')->get()->first();
        // if ($employeeStatus->status === 'Retired') {
        //     return response()->json(['errorMsg' => "This employee is retired!"], 400);
        // }

        $employeeBank = EmployeeBankAccount::find($request['employee_bank_id']);
        // if (!$employeeBank || $employeeBank->employee_id != $employee->id) return response()->json(['errorMsg' => 'Employee Bank not found!'], 404);
        // if (!$employeeBank->is_active) return response()->json(['errorMsg' => 'Please fill the correct Bank'], 400);

        // $employeePayStructure = EmployeePayStructure::with('payMatrixCell.payMatrixLevel')->find($request['pay_structure_id']);
        // if (!$employeePayStructure) {
        //     return response()->json(['errorMsg' => 'Employee Pay Structure not found!'], 404);
        // }
        // $employeePayCell = $employeePayStructure->payMatrixCell;
        // $employeePayLevel = $employeePayStructure->payMatrixCell->payMatrixLevel;
        // $basicPay = $employeePayStructure->PayMatrixCell->amount;  // Basic Pay

        // $taRate = EmployeeTransportAllowance::where('pay_matrix_level', $employeePayLevel->name)->first();
        // if (!$taRate) {
        //     return response()->json(['errorMsg' => 'Transport Allowance not found!'], 404);
        // }

        // if ($employee->npa_eligibility) {
        //     $nonPracticingAllowance = NonPracticingAllowanceRate::find($request['npa_rate_id']);
        //     if (!$nonPracticingAllowance) {
        //         return response()->json(['errorMsg' => 'Non Practicing Allowance not found!'], 404);
        //     }
        //     $npaAmount += $basicPay * ($nonPracticingAllowance->rate_percentage / 100);
        // }

        // if ($basicPay > 237500) {
        //     $payPlusNpa = $basicPay;
        //     $npaAmount = 0;
        // } else {
        //     $payPlusNpa = $basicPay + $npaAmount;
        //     if ($payPlusNpa > 237500) {
        //         $payPlusNpa = 237500;
        //         $npaAmount = $payPlusNpa - $basicPay;
        //     }
        // }

        // $employeeQuarter = EmployeeQuarter::where('employee_id', $employee->id)->orderBy('date_of_occupation', 'DESC')->first();
        // $houseRentAllowance = HouseRentAllowanceRate::find($request['hra_rate_id']);
        // if ($employee->hra_eligibility) {
        //     if (!$houseRentAllowance) {
        //         return response()->json(['errorMsg' => 'House Rent Allowance not found!'], 404);
        //     }
        //     $hraAmount += $payPlusNpa * ($houseRentAllowance->rate_percentage / 100);
        // } else {
        //     $today = \Carbon\Carbon::today();
        //     if ($employeeQuarter && $employeeQuarter->date_of_occupation <= $today) {
        //         if ($employeeQuarter->date_of_leaving === null || $today <= $employeeQuarter->date_of_leaving) {
        //             // Employee is currently occupying the quarter, apply license fee
        //             $quarter = Quarter::find($employeeQuarter->quarter_id);
        //             if ($quarter) {
        //                 $license_fee = (float) $quarter->license_fee;
        //             }
        //         } else {
        //             // Employee has left the quarter, provide HRA
        //             if (!$houseRentAllowance) {
        //                 return response()->json(['errorMsg' => 'House Rent Allowance not found!'], 404);
        //             }
        //             $hraAmount += $payPlusNpa * ($houseRentAllowance->rate_percentage / 100);
        //         }
        //     } else {
        //         // Employee doesn't have a quarter or hasn't occupied it yet, provide HRA
        //         if (!$houseRentAllowance) {
        //             return response()->json(['errorMsg' => 'House Rent Allowance not found!'], 404);
        //         }
        //         $hraAmount += $payPlusNpa * ($houseRentAllowance->rate_percentage / 100);
        //     }
        // }

        // if ($employee->uniform_allowance_eligibility) {
        //     $uniformAllowance = UniformAllowanceRate::find($request['uniform_rate_id']);
        //     if (!$uniformAllowance) {
        //         return response()->json(['errorMsg' => 'Uniform Allowance Rate not found!'], 404);
        //     }
        //     $uaRateAmount = $uniformAllowance->amount;
        // } else {
        //     $uaRateAmount = 0;
        // }

        // $dearnessAllowanceRate = DearnesAllowanceRate::find($request['da_rate_id']);
        // if (!$dearnessAllowanceRate) {
        //     return response()->json(['errorMsg' => 'Dearness Allowance Rate not found!'], 404);
        // }

        // $employeeGIS = GISEligibility::where('pay_matrix_level', $employeePayLevel->name)->get()->first();
        // if (!$employeeGIS) {
        //     return response()->json(['errorMsg' => 'Employee GIS not found!'], 404);
        // }
        // if ($employee->gis_eligibility) {
        //     $gisAmount = $employeeGIS->amount;
        // }

        if ($employee->credit_society_member) {
            if (!$request['credit_society_membership']) {
                return response()->json(['errorMsg' => 'Please fill the credit society membership!']);
            }
        }

        $employeeLoan = LoanAdvance::where('employee_id', $employee->id)->where('loan_type', 'Computer')->get()->first();

        DB::beginTransaction();
        $computer_advance_inst_no = 0;
        if ($employeeLoan && $employeeLoan->remaining_balance > 0) {
            $employeeLoan->remaining_balance = $employeeLoan->remaining_balance - $request['computer_advance_installment'];
            $employeeLoan->current_installment += 1;
            $computer_advance_inst_no = $employeeLoan->current_installment;
            try {
                $employeeLoan->save();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['errorMsg' => $e->getMessage()], 500);
            }
        }

        $netSalary = new NetSalary();
        $netSalary->employee_id = $request['employee_id'];
        $netSalary->month = $request['month'];
        $netSalary->year = $request['year'];
        $netSalary->processing_date = $request['processing_date'];
        $netSalary->payment_date = $request['payment_date'];
        $netSalary->net_amount = $request['net_amount'];
        $netSalary->employee_bank_id = $request['employee_bank_id'];
        $netSalary->remarks = $request['remarks'];
        $netSalary->employee = $employee ?? null;
        $netSalary->employee_bank = $employeeBank ?? null;
        $netSalary->added_by = auth()->id();

        try {
            $netSalary->save();

            // return response()->json(['successMsg' => 'Net Salary Created!', 'data' => $netSalary]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }

        $net_salary = $netSalary;


        // if ($net_salary->employee_id != $employeePayStructure->employee_id) {
        //     DB::rollBack();
        //     return response()->json(['errorMsg' => 'Employee Net Salary and Employee Pay Structure not matched!']);
        // }

        // if ($employeeCurrentStatus->status == 'Active') {
        // if ($employee->pwd_status) {
        //     $taAmount += 2 * $taRate->amount;
        // } else {
        //     $taAmount += $taRate->amount;
        // }
        // $daOnTa = $taAmount * ($dearnessAllowanceRate->rate_percentage) / 100;

        // $daRateAmount = $payPlusNpa * ($dearnessAllowanceRate->rate_percentage / 100);

        $salary_date = Carbon::create($request['year'], $request['month'], 1)->toDateString();
        $nps_govt = \App\Models\NPSGovtContribution::where('type', 'GOVT')->get()->last();
        $nps_employee = \App\Models\NPSGovtContribution::where('type', 'Employee')->get()->last();
        $govt_rate = $nps_govt ? $nps_govt->rate_percentage : 14;
        $employee_rate = $nps_employee ? $nps_employee->rate_percentage : 10;

        if ($employee->pension_scheme === 'NPS') {
            $govt_contribution_14 = ($payPlusNpa + $daRateAmount) * $govt_rate / 100;
            $employee_contribution_10 = ($payPlusNpa + $daRateAmount) * $employee_rate / 100;
        }


        // } 
        // else if ($employeeCurrentStatus->status == 'Suspended') {
        //     $employeeQuarter = EmployeeQuarter::where('employee_id', $employee->id)->orderBy('date_of_occupation', 'DESC')->first();
        //     if ($employee->hra_eligibility) {
        //         $hraAmount += $basicPay * 0.27;
        //     } else {
        //         if (!$employeeQuarter->is_occupied) {
        //             $hraAmount += $basicPay * 0.27;
        //         }
        //     }

        //     $basicPay = $basicPay / 2;
        // }

        $salaryArrearTotal = 0;
        if ($request->filled('salary_arrears')) {
            foreach ($request->salary_arrears as $item) {
                $salaryArrearTotal += $item['amount'];
            }
        }

        $deductionRecoveryTotal = 0;
        if ($request->filled('deduction_recoveries')) {
            foreach ($request->deduction_recoveries as $item) {
                $deductionRecoveryTotal += $item['amount'];
            }
        }

        $sum = $govt_contribution_14 +
            $request['spacial_pay'] +
            $request['arrears'] +
            $request['da_1'] +
            $request['da_2'] +
            $request['itc_leave_salary'] +
            $salaryArrearTotal;

        // $totalBasicSalary = $request['basic_pay'] + $request['npa_amount']  + $taAmount + $hraAmount + $daRateAmount + $uaRateAmount + $daOnTa + $sum;

        $total_deductions =
            ($request->income_tax ?? 0) +
            ($request->professional_tax ?? 0) +
            ($request->nfch_donation ?? 0) +
            ($request->gpf ?? 0) +
            ($request->transport_allowance_recovery ?? 0) +
            ($request->hra_recovery ?? 0) +
            ($request->license_fee ?? 0) +
            ($request->computer_advance_installment ?? 0) +
            ($request->computer_advance_balance ?? 0) +
            ($request->employee_contribution_10 ?? 0) +
            ($request->govt_contribution_14_recovery ?? 0) +
            ($request->dies_non_recovery ?? 0) +
            ($request->computer_advance_interest ?? 0) +
            ($request->pay_recovery ?? 0) +
            ($request->nps_recovery ?? 0) +
            ($request->lic ?? 0) +
            ($request->credit_society_membership ?? 0) +
            ($request->gis ?? 0) +
            $deductionRecoveryTotal;

        // $net_salary->net_amount = $totalBasicSalary - $totalDeduction;
        try {
            $net_salary->save();

            $pay_slip = $net_salary->paySlip()->create([
                'pay_structure_id' => $request['pay_structure_id'],
                'basic_pay' => $request['basic_pay'],
                'da_rate_id' => $request['da_rate_id'],
                'da_amount' => $request['da_amount'],
                'hra_rate_id' => $employee->hra_eligibility ? $request['hra_rate_id'] : null,
                'hra_amount' => $request['hra_amount'],
                'npa_rate_id' => $employee->npa_eligibility ? $request['npa_rate_id'] : null,
                'npa_amount' => $request['npa_amount'],
                'transport_rate_id' => $request['transport_rate_id'],
                'transport_amount' => $request['transport_amount'],
                'uniform_rate_id' => $employee->uniform_allowance_eligibility ? $request['uniform_rate_id'] : null,
                'uniform_rate_amount' => $request['uniform_rate_amount'],
                'govt_contribution' => $request['govt_contribution_14_recovery'],
                'da_on_ta' =>  $request['da_on_ta'],
                'arrears' => $request['arrears'],
                'spacial_pay' => $request['spacial_pay'],
                'da_1' => $request['da_1'],
                'da_2' => $request['da_2'],
                'itc_leave_salary' => $request['itc_leave_salary'],
                'total_pay' => $request['total_pay'],
                'added_by' => auth()->id(),
            ]);
            // return response()->json(['successMsg' => 'Pay Slip created!', 'data' => [$net_salary, $pay_slip]]);

            if ($request->filled('salary_arrears')) {
                foreach ($request->salary_arrears as $item) {
                    SalaryArrears::create([
                        'pay_slip_id' => $pay_slip->id,
                        'type' => $item['type'],
                        'amount' => $item['amount'],
                        'added_by' => auth()->id(),
                    ]);
                }
            }

            $deduction = $net_salary->deduction()->create([
                'income_tax' => $request['income_tax'],
                'professional_tax' => $request['professional_tax'],
                'license_fee' => $request['license_fee'],
                'nfch_donation' => $request['nfch_donation'],
                'gpf' => $request['gpf'],
                'transport_allowance_recovery' => $request['transport_allowance_recovery'],
                'hra_recovery' => $request['hra_recovery'],
                'computer_advance' => $request['computer_advance'],
                'computer_advance_installment' => $request['computer_advance_installment'],
                'computer_advance_inst_no' => $computer_advance_inst_no ?? 0,
                'computer_advance_balance' => $employeeLoan->remaining_balance ?? 0,
                'employee_contribution_10' =>  $request['employee_contribution_10'],
                'govt_contribution_14_recovery' => $request['govt_contribution_14_recovery'],
                'dies_non_recovery' => $request['dies_non_recovery'],
                'computer_advance_interest' => $employeeLoan->loan_amount ?? 0,
                'gis' => $request['gis'],
                'pay_recovery' => $request['pay_recovery'],
                'nps_recovery' => $request['nps_recovery'],
                'lic' => $request['lic'],
                'credit_society' => $request['credit_society_membership'],
                'total_deductions' => $total_deductions,
                'added_by' => auth()->id(),
            ]);
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

            $net_salary->net_amount = $pay_slip->total_pay - $total_deductions;
            $net_salary->save();

            DB::commit();
            return response()->json(['successMsg' => 'Pay Slip created!', 'data' => [$net_salary, $pay_slip, $deduction]]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $salaryPay = PaySlip::find($id);
        if (!$salaryPay) return response()->json(['errorMsg' => 'Pay slip not found!'], 404);

        $request->validate([
            'net_salary_id' => 'nullable|numeric|exists:net_salaries,id',
            'pay_structure_id' => 'required|numeric|exists:employee_pay_structures,id',
            'basic_pay' => 'required|numeric',
            'da_rate_id' => 'required|numeric|exists:dearnes_allowance_rates,id',
            'da_amount' => 'required|numeric',
            'hra_rate_id' => 'nullable|numeric|exists:house_rent_allowance_rates,id',
            'hra_amount' => 'nullable|numeric',
            'npa_rate_id' => 'nullable|numeric|exists:non_practicing_allowance_rates,id',
            'npa_amount' => 'nullable|numeric',
            'transport_rate_id' => 'nullable|numeric|exists:transport_allowance_rates,id',
            'transport_amount' => 'nullable|numeric',
            'uniform_rate_id' => 'nullable|numeric|exists:uniform_allowance_rates,id',
            'uniform_rate_amount' => 'nullable|numeric',
            'arrears' => 'nullable|numeric',
            'da_on_ta' => 'nullable|numeric',
            'spacial_pay' => 'nullable|numeric',
            'da_1' => 'nullable|numeric',
            'da_2' => 'nullable|numeric',
            'itc_leave_salary' => 'nullable|numeric',
            'total_pay' => 'required|numeric',
            'remarks' => 'nullable|string',
        ]);

        // $employeeBank = EmployeeBankAccount::find($request['employee_bank_id']);
        // if (!$employeeBank->is_active) return response()->json(['errorMsg' => 'Please fill the correct Bank'], 400);
        // if ($employeeBank->employee_id != $request['employee_id']) return response()->json(['errorMsg' => 'Employee Bank not found!'], 404);


        $net_salary = NetSalary::with('deduction', 'paySlip')->find($salaryPay->net_salary_id);
        if (!$net_salary) {
            return response()->json(['errorMsg' => 'Net Salary not found!']);
        }

        $employee = Employee::find($net_salary->employee_id);
        if (!$employee) return response()->json(['errorMsg' => 'Employee not found!'], 404);

        // $employeeStatus = EmployeeStatus::where('employee_id', $employee->id)->orderBy('effective_from', 'DESC')->get();
        // $employeeCurrentStatus = $employeeStatus[0];


        $taAmount = 0; // Transport Allowance
        // $gisAmount = 0;
        // // $csmAmount = 0; // Credit society Member
        $npaAmount = 0; // Non Practicing Allowance
        $hraAmount = 0; // House Rent Allowance
        $daRateAmount = 0; // Dearness Allowance Rate
        $uaRateAmount = 0; // Uniform Allowance Rate 
        $license_fee = 0; // License Fee from Quarter
        // $totalBasicSalary = 0;
        $payPlusNpa = 0;

        $employeePayStructure = EmployeePayStructure::with('payMatrixCell.payMatrixLevel')->find($request['pay_structure_id']);
        if (!$employeePayStructure) {
            return response()->json(['errorMsg' => 'Employee Pay Structure not found!'], 404);
        }

        if ($net_salary->employee_id != $employeePayStructure->employee_id) {
            return response()->json(['errorMsg' => 'Employee Net Salary and Employee Pay Structure not matched!']);
        }

        $employeePayCell = $employeePayStructure->payMatrixCell;
        $employeePayLevel = $employeePayStructure->payMatrixCell->payMatrixLevel;
        $basicPay = $employeePayStructure->PayMatrixCell->amount;



        // if ($employee->npa_eligibility) {
        //     $nonPracticingAllowance = NonPracticingAllowanceRate::find($request['npa_rate_id']);
        //     if (!$nonPracticingAllowance) {
        //         return response()->json(['errorMsg' => 'Non Practicing Allowance not found!'], 404);
        //     }
        //     $npaAmount += $basicPay * ($nonPracticingAllowance->rate_percentage / 100);
        // }

        // if ($basicPay > 237500) {
        //     $payPlusNpa = $basicPay;
        //     $npaAmount = 0;
        // } else {
        //     $payPlusNpa = $basicPay + $npaAmount;
        //     if ($payPlusNpa > 237500) {
        //         $payPlusNpa = 237500;
        //         $npaAmount = $payPlusNpa - $basicPay;
        //     }
        // }

        if ($employee->uniform_allowance_eligibility) {
            $uniformAllowance = UniformAllowanceRate::find($request['uniform_rate_id']);
            if (!$uniformAllowance) {
                return response()->json(['errorMsg' => 'Uniform Allowance Rate not found!'], 404);
            }
            $uaRateAmount = $uniformAllowance->amount;
        } else {
            $uaRateAmount = 0;
        }

        $payMatrixCell = PayMatrixCell::with('payMatrixLevel')->where('index', $employeePayCell->index)->orderBy('amount', 'DESC')->get();
        $dearnessAllowanceRate = DearnesAllowanceRate::find($request['da_rate_id']);
        if (!$dearnessAllowanceRate) {
            return response()->json(['errorMsg' => 'Dearness Allowance Rate not found!'], 404);
        }
        $totalOfBasicPayAndNPA = $basicPay + $npaAmount;
        if ($totalOfBasicPayAndNPA >= 237500) {
            $totalOfBasicPayAndNPA = 237500;
        }
        $daRateAmount = $totalOfBasicPayAndNPA * ($dearnessAllowanceRate->rate_percentage / 100);

        $taRate = EmployeeTransportAllowance::where('pay_matrix_level', $employeePayLevel->name)->first();
        if (!$taRate) {
            return response()->json(['errorMsg' => 'Transport Allowance not found!'], 404);
        }

        if ($employee->pwd_status) {
            $taAmount += 2 * $taRate->amount;
        } else {
            $taAmount += $taRate->amount;
        }
        $daOnTa = $taAmount * ($dearnessAllowanceRate->rate_percentage) / 100;

        $salaryArrearTotal = 0;
        if ($request->filled('salary_arrears')) {
            foreach ($request->salary_arrears as $item) {
                $salaryArrearTotal += $item['amount'];
            }
        }

        $sum =
            $request['govt_contribution'] +
            $request['pay_plus_npa'] +
            $request['spacial_pay'] +
            $request['arrears'] +
            $request['da_1'] +
            $request['da_2'] +
            $request['itc_leave_salary'] +
            $salaryArrearTotal;

        $totalBasicSalary = $basicPay + $taAmount + $npaAmount + $hraAmount + $daRateAmount + $uaRateAmount + $daOnTa + $sum;

        DB::beginTransaction();

        // return response()->json([
        //     'successMsg' => 'Pay Slip updated!',
        //     'data' => [$net_salary_old_data]
        // ]);
        $net_salary_old_data = $net_salary->toArray();
        $net_salary_old_data['pay_structure_id'] = $salaryPay->pay_structure_id;
        $net_salary_old_data['basic_pay'] = $salaryPay->basic_pay ?? 0;
        $net_salary_old_data['da_rate_id'] = $salaryPay->da_rate_id;
        $net_salary_old_data['da_amount'] = $salaryPay->da_amount ?? 0;
        $net_salary_old_data['hra_rate_id'] = $salaryPay->hra_rate_id ?? 0;
        $net_salary_old_data['hra_amount'] = $salaryPay->hra_amount ?? 0;
        $net_salary_old_data['npa_rate_id'] = $salaryPay->npa_rate_id;
        $net_salary_old_data['npa_amount'] = $salaryPay->npa_amount ?? 0;
        $net_salary_old_data['transport_rate_id'] = $salaryPay->transport_rate_id;
        $net_salary_old_data['transport_amount'] = $salaryPay->transport_amount ?? 0;
        $net_salary_old_data['uniform_rate_id'] = $salaryPay->uniform_rate_id;
        $net_salary_old_data['uniform_rate_amount'] = $salaryPay->uniform_rate_amount ?? 0;
        $net_salary_old_data['pay_plus_npa'] = $salaryPay->pay_plus_npa ?? 0;
        $net_salary_old_data['govt_contribution'] = $salaryPay->govt_contribution ?? 0;
        $net_salary_old_data['da_on_ta'] = $salaryPay->da_on_ta ?? 0;
        $net_salary_old_data['arrears'] = $salaryPay->arrears ?? 0;
        $net_salary_old_data['spacial_pay'] = $salaryPay->spacial_pay ?? 0;
        $net_salary_old_data['da_1'] = $salaryPay->da_1 ?? 0;
        $net_salary_old_data['da_2'] = $salaryPay->da_2 ?? 0;
        $net_salary_old_data['itc_leave_salary'] = $salaryPay->itc_leave_salary ?? 0;
        $net_salary_old_data['total_pay'] = $salaryPay->total_pay ?? 0;
        $net_salary_old_data['income_tax'] = $net_salary->deduction->income_tax ?? 0;
        $net_salary_old_data['professional_tax'] = $net_salary->deduction->professional_tax ?? 0;
        $net_salary_old_data['license_fee'] = $net_salary->deduction->license_fee ?? 0;
        $net_salary_old_data['nfch_donation'] = $net_salary->deduction->nfch_donation ?? 0;
        $net_salary_old_data['gpf'] = $net_salary->deduction->gpf ?? 0;
        $net_salary_old_data['transport_allowance_recovery'] = $net_salary->deduction->transport_allowance_recovery ?? 0;
        $net_salary_old_data['hra_recovery'] = $net_salary->deduction->hra_recovery ?? 0;
        $net_salary_old_data['computer_advance'] = $net_salary->deduction->computer_advance ?? 0;
        $net_salary_old_data['computer_advance_installment'] = $net_salary->deduction->computer_advance_installment ?? 0;
        $net_salary_old_data['computer_advance_inst_no'] = $net_salary->deduction->computer_advance_inst_no ?? 0;
        $net_salary_old_data['computer_advance_balance'] = $net_salary->deduction->computer_advance_balance ?? 0;
        $net_salary_old_data['employee_contribution_10'] = $net_salary->deduction->employee_contribution_10 ?? 0;
        $net_salary_old_data['govt_contribution_14_recovery'] = $net_salary->deduction->govt_contribution_14_recovery ?? 0;
        $net_salary_old_data['dies_non_recovery'] = $net_salary->deduction->dies_non_recovery ?? 0;
        $net_salary_old_data['computer_advance_interest'] = $net_salary->deduction->computer_advance_interest ?? 0;
        $net_salary_old_data['gis'] = $net_salary->deduction->gis ?? 0;
        $net_salary_old_data['pay_recovery'] = $net_salary->deduction->pay_recovery ?? 0;
        $net_salary_old_data['nps_recovery'] = $net_salary->deduction->nps_recovery ?? 0;
        $net_salary_old_data['lic'] = $net_salary->deduction->lic ?? 0;
        $net_salary_old_data['credit_society'] = $net_salary->deduction->credit_society ?? 0;
        $net_salary_old_data['total_deductions'] = $net_salary->deduction->total_deductions ?? 0;
        $net_salary_old_data['salary_arrears'] =  json_encode($salaryPay->salaryArrears) ?? null;
        $net_salary_old_data['deduction_recoveries'] = json_encode($net_salary?->deduction->deductionRecoveries) ?? null;
        // return response()->json([
        //     'successMsg' => 'Pay Slip updated!',
        //     'data' => $net_salary->deduction->deductionRecoveries
        // ]);

        $old_data = $salaryPay->toArray();

        // $salaryPay->net_salary_id = $net_salary->id;
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
        $salaryPay->govt_contribution = $request['govt_contribution'];
        $salaryPay->da_on_ta = $request['da_on_ta'];
        $salaryPay->arrears = $request['arrears'];
        $salaryPay->spacial_pay = $request['spacial_pay'];
        $salaryPay->da_1 = $request['da_1'];
        $salaryPay->da_2 = $request['da_2'];
        $salaryPay->itc_leave_salary = $request['itc_leave_salary'];
        $salaryPay->total_pay = $request['total_pay'];
        $salaryPay->edited_by = auth()->id();

        try {
            $salaryPay->save();

            if ($net_salary->deduction) {
                $net_salary->net_amount = $salaryPay->total_pay - $net_salary->deduction->total_deductions;
            }
            $net_salary->remarks = $request['remarks'];
            $net_salary->edited_by = auth()->id();

            $net_salary->save();



            $net_salary_clone = $net_salary->history()->create($net_salary_old_data);
            // $old_data['net_salary_clone_id'] = $net_salary_clone->id;

            // $paySlipClone = $salaryPay->history()->create($old_data);

            // $old_deduction_data = $net_salary->deduction->toArray();
            // $old_deduction_data['deduction_id'] = $net_salary->deduction->id;
            // $old_deduction_data['net_salary_clone_id'] = $net_salary_clone->id;
            // $deductionClone = DeductionClone::create($old_deduction_data);

            // $old_recoveries = DeductionRecoveries::where('deduction_id', $net_salary->deduction->id)->get()->toArray();
            // foreach ($old_recoveries as $old_recovery) {
            //     unset($old_recovery['id']); // remove ID to avoid duplication
            //     $old_recovery['net_salary_clone_id'] = $net_salary_clone->id;
            //     $old_recovery['duduction_id'] = $net_salary->deduction->id;
            //     $old_recovery['deduction_clone_id'] = $deductionClone->id;

            //     // If you have history() relation (recommended)
            //     DeductionRecoveryClones::create($old_recovery);

            //     // OR, if you're saving in a separate `salary_arrear_histories` table manually
            //     // SalaryArrearHistory::create($old_arrear);
            // }

            $old_arrears = SalaryArrears::where('pay_slip_id', $salaryPay->id)->get()->toArray();
            foreach ($old_arrears as $old_arrear) {
                unset($old_arrear['id']); // remove ID to avoid duplication
                $old_arrear['net_salary_clone_id'] = $net_salary_clone->id;
                $old_arrear['pay_slip_id'] = $salaryPay->id;
                // $old_arrear['pay_slip_clone_id'] = $paySlipClone->id;

                // If you have history() relation (recommended)
                SalaryArrearClone::create($old_arrear);

                // OR, if you're saving in a separate `salary_arrear_histories` table manually
                // SalaryArrearHistory::create($old_arrear);
            }

            // Delete previous arrears
            SalaryArrears::where('pay_slip_id', $salaryPay->id)->delete();

            // Recreate from new request data
            if ($request->filled('salary_arrears')) {
                foreach ($request->salary_arrears as $item) {
                    SalaryArrears::create([
                        'pay_slip_id' => $salaryPay->id,
                        'type' => $item['type'],
                        'amount' => $item['amount'],
                        'added_by' => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'successMsg' => 'Pay Slip updated!',
                'data' => [$salaryPay, $net_salary]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function bulkStore(Request $request)
    {
        $request->validate([
            'month' => 'required|numeric|max:12|min:1',
            'year' => 'required|numeric|digits:4|min:1900',
            'processing_date' => 'required|date',
            'payment_date' => 'nullable|date',
        ]);

        // Calculate previous month and year
        $previousMonth = $request['month'] - 1;
        $previousYear = $request['year'];
        if ($previousMonth == 0) {
            $previousMonth = 12;
            $previousYear = $request['year'] - 1;
        }

        // Fetch previous month's salary data
        $previousMonthSalaries = NetSalary::with('paySlip', 'deduction')
            ->where('month', $previousMonth)
            ->where('year', $previousYear)
            ->where('is_verified', 1)
            ->get();

        if ($previousMonthSalaries->isEmpty()) {
            return response()->json(['errorMsg' => 'No previous month salary data found!'], 404);
        }

        $generatedCount = 0;
        $errors = [];
        $errors = [];

        foreach ($previousMonthSalaries as $previousSalary) {
            // Check if salary for current month already exists
            $existingSalary = NetSalary::where('employee_id', $previousSalary->employee_id)
                ->where('month', $request['month'])
                ->where('year', $request['year'])
                ->first();

            try {
                // Validate employee and bank account
                $employee = Employee::with(
                    'employeeDesignation',
                    'employeeStatus',
                    'employeePayStructure.PayMatrixCell.payMatrixLevel',
                    'employeeBank',
                    'latestEmployeeDesignation'
                )->find($previousSalary->employee_id);
                if (!$employee) {
                    $errors[] = "Employee not found for ID: {$previousSalary->employee_id}";
                    continue;
                }


                if ($existingSalary) {
                    $monthName = \Carbon\Carbon::create()->month($request->month)->format('F');

                    $errors[] = "Salary for employee {$employee->name} already exists for {$monthName} - {$request->year}";
                    continue;
                }

                $user = User::where('is_retired', 1)->find($employee->user_id);
                if ($user) {
                    $errors[] = "Employee is retired: {$employee->name}";
                    continue;
                }

                $employeeBank = EmployeeBankAccount::find($previousSalary->employee_bank_id);
                if (!$employeeBank || $employeeBank->employee_id != $employee->id || !$employeeBank->is_active) {
                    $errors[] = "Invalid bank account for employee Bank: {$previousSalary->employee->name}";
                    continue;
                }

                $employeeStatus = EmployeeStatus::where('employee_id', $employee->id)->orderBy('effective_from', 'DESC')->get()->first();
                if ($employeeStatus->status === 'Retired') {
                    $errors[] = "{$previousSalary->employee->name} is retired!";
                    continue;
                }

                if ($employeeStatus->status === 'Resigned') {
                    $errors[] = "{$previousSalary->employee->name} is resigned!";
                    continue;
                }

                DB::beginTransaction();

                $employeeLoan = LoanAdvance::where('employee_id', $employee->id)->where('loan_type', 'Computer')->get()->first();

                $computer_advance_inst_no = 0;
                if ($employeeLoan && $employeeLoan->remaining_balance > 0) {
                    $employeeLoan->remaining_balance = $employeeLoan->remaining_balance - $previousSalary->deduction->computer_advance_installment;
                    $employeeLoan->current_installment += 1;
                    $computer_advance_inst_no = $employeeLoan->current_installment;
                    try {
                        $employeeLoan->save();
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $errors = "Add Loan error for the {$employee->name} " . $e->getMessage();
                    }
                }

                $payComponentsSum = 0;
                $totalDeductions = 0;

                // Create new NetSalary record with previous month's data
                $newNetSalary = new NetSalary();
                $newNetSalary->employee_id = $previousSalary->employee_id;
                $newNetSalary->month = $request['month'];
                $newNetSalary->year = $request['year'];
                $newNetSalary->processing_date = $request['processing_date'];
                $newNetSalary->payment_date = null;
                $newNetSalary->employee_bank_id = $previousSalary->employee_bank_id;
                $newNetSalary->employee = $employee ?? null;
                $newNetSalary->employee_bank = $employeeBank ?? null;
                $newNetSalary->added_by = auth()->id();
                $newNetSalary->net_amount = 0;

                $newNetSalary->save();

                // Create new PaySlip record with previous month's data
                if ($previousSalary->paySlip) {
                    $newPaySlip = new PaySlip();
                    $newPaySlip->net_salary_id = $newNetSalary->id;
                    $newPaySlip->pay_structure_id = $previousSalary->paySlip->pay_structure_id;
                    $newPaySlip->basic_pay = $previousSalary->paySlip->basic_pay;
                    $newPaySlip->da_rate_id = $previousSalary->paySlip->da_rate_id;
                    $newPaySlip->da_amount = $previousSalary->paySlip->da_amount;
                    $newPaySlip->hra_rate_id = $previousSalary->paySlip->hra_rate_id;
                    $newPaySlip->hra_amount = $previousSalary->paySlip->hra_amount;
                    $newPaySlip->npa_rate_id = $previousSalary->paySlip->npa_rate_id;
                    $newPaySlip->npa_amount = $previousSalary->paySlip->npa_amount;
                    $newPaySlip->transport_rate_id = $previousSalary->paySlip->transport_rate_id;
                    $newPaySlip->transport_amount = $previousSalary->paySlip->transport_amount;
                    $newPaySlip->uniform_rate_id = $previousSalary->paySlip->uniform_rate_id;
                    $newPaySlip->uniform_rate_amount = $previousSalary->paySlip->uniform_rate_amount;
                    $newPaySlip->pay_plus_npa = $previousSalary->paySlip->pay_plus_npa;
                    $newPaySlip->govt_contribution = $previousSalary->paySlip->govt_contribution;
                    $newPaySlip->da_on_ta = $previousSalary->paySlip->da_on_ta;
                    $newPaySlip->arrears = $previousSalary->paySlip->arrears;
                    $newPaySlip->spacial_pay = $previousSalary->paySlip->spacial_pay;
                    $newPaySlip->da_1 = $previousSalary->paySlip->da_1;
                    $newPaySlip->da_2 = $previousSalary->paySlip->da_2;
                    $newPaySlip->itc_leave_salary = $previousSalary->paySlip->itc_leave_salary;

                    $payComponentsSum =
                        $newPaySlip->basic_pay +
                        $newPaySlip->da_amount +
                        $newPaySlip->hra_amount +
                        $newPaySlip->npa_amount +
                        $newPaySlip->transport_amount +
                        $newPaySlip->uniform_rate_amount +
                        $newPaySlip->pay_plus_npa +
                        $newPaySlip->govt_contribution +
                        $newPaySlip->da_on_ta +
                        $newPaySlip->arrears +
                        $newPaySlip->spacial_pay +
                        $newPaySlip->da_1 +
                        $newPaySlip->da_2 +
                        $newPaySlip->itc_leave_salary;

                    $newPaySlip->total_pay = $payComponentsSum;
                    $newPaySlip->added_by = auth()->id();

                    $newPaySlip->save();

                    $salaryArrearSum = 0;
                    if ($previousSalary->paySlip->salaryArrears) {
                        foreach ($previousSalary->paySlip->salaryArrears as $item) {
                            SalaryArrears::create([
                                'pay_slip_id' => $newPaySlip->id,
                                'type' => $item->type,
                                'amount' => $item->amount,
                                'added_by' => auth()->id(),
                            ]);
                            $salaryArrearSum += $item->amount;
                        }
                    }

                    // ✅ Update total_pay with arrears included
                    $newPaySlip->total_pay += $salaryArrearSum;
                    $newPaySlip->save();
                }
                // Create new Deduction record with previous month's data
                if ($previousSalary->deduction) {
                    $newDeduction = new Deduction();
                    $newDeduction->net_salary_id = $newNetSalary->id;
                    $newDeduction->income_tax = $previousSalary->deduction->income_tax;
                    $newDeduction->professional_tax = $previousSalary->deduction->professional_tax;
                    $newDeduction->license_fee = $previousSalary->deduction->license_fee;
                    $newDeduction->nfch_donation = $previousSalary->deduction->nfch_donation;
                    $newDeduction->gpf = $previousSalary->deduction->gpf;
                    $newDeduction->hra_recovery = $previousSalary->deduction->hra_recovery;
                    $newDeduction->transport_allowance_recovery = $previousSalary->deduction->transport_allowance_recovery;
                    $newDeduction->computer_advance = $previousSalary->deduction->computer_advance ?? 0;
                    $newDeduction->computer_advance_installment = $previousSalary->deduction->computer_advance_installment ?? 0;
                    $newDeduction->computer_advance_inst_no = $computer_advance_inst_no ?? 0;
                    $newDeduction->computer_advance_balance = $employeeLoan->remaining_balance ?? 0;
                    $newDeduction->employee_contribution_10 = $previousSalary->deduction->employee_contribution_10;
                    $newDeduction->govt_contribution_14_recovery = $previousSalary->deduction->govt_contribution_14_recovery;
                    $newDeduction->dies_non_recovery = $previousSalary->deduction->dies_non_recovery;
                    $newDeduction->computer_advance_interest = $employeeLoan->loan_amount ?? 0;
                    $newDeduction->gis = $previousSalary->deduction->gis;
                    $newDeduction->pay_recovery = $previousSalary->deduction->pay_recovery;
                    $newDeduction->nps_recovery = $previousSalary->deduction->nps_recovery;
                    $newDeduction->lic = $previousSalary->deduction->lic;
                    $newDeduction->credit_society = $previousSalary->deduction->credit_society;

                    $totalDeductions =
                        $newDeduction->income_tax +
                        $newDeduction->professional_tax +
                        $newDeduction->license_fee +
                        $newDeduction->nfch_donation +
                        $newDeduction->gpf +
                        $newDeduction->hra_recovery +
                        $newDeduction->transport_allowance_recovery +
                        $newDeduction->computer_advance +
                        $newDeduction->computer_advance_installment +
                        $newDeduction->computer_advance_balance +
                        $newDeduction->employee_contribution_10 +
                        $newDeduction->govt_contribution_14_recovery +
                        $newDeduction->dies_non_recovery +
                        $newDeduction->computer_advance_interest +
                        $newDeduction->gis +
                        $newDeduction->pay_recovery +
                        $newDeduction->nps_recovery +
                        $newDeduction->lic +
                        $newDeduction->credit_society;

                    $newDeduction->total_deductions = $totalDeductions;
                    $newDeduction->added_by = auth()->id();

                    $newDeduction->save();

                    $deductionRecoveriesSum = 0;
                    if ($previousSalary->deduction->deductionRecoveries) {
                        foreach ($previousSalary->deduction->deductionRecoveries as $item) {
                            DeductionRecoveries::create([
                                'deduction_id' => $newDeduction->id,
                                'type' => $item->type,
                                'amount' => $item->amount,
                                'added_by' => auth()->id(),
                            ]);
                            $deductionRecoveriesSum += $item->amount;
                        }
                    }

                    // ✅ Update total_deductions
                    $newDeduction->total_deductions += $deductionRecoveriesSum;
                    $newDeduction->save();
                }


                $newNetSalary->net_amount = $newPaySlip->total_pay - $newDeduction->total_deductions;
                $newNetSalary->save();

                DB::commit();
                $generatedCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = "Error processing employee ID {$previousSalary->employee_id}: " . $e->getMessage();
            }
        }

        // Fetch generated salaries for response
        $generatedSalaries = Employee::whereHas(
            'netSalary',
            fn($q) => $q->where('month', $request['month'])
                ->where('year', $request['year'])
        )->with([
            'netSalary' => fn($q) => $q->where('month', $request['month'])
                ->where('year', $request['year'])
        ])->get();

        $response = [
            'successMsg' => "Successfully generated {$generatedCount} salary records!",
            'data' => $generatedSalaries
        ];

        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }
        // if (!empty($errors)) {
        //     $response['skipped_employees'] = $errors;
        // }

        return response()->json($response);
    }
}
