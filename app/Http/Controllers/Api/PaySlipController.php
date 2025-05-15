<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DearnesAllowanceRate;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeePayStructure;
use App\Models\EmployeeQuarter;
use App\Models\EmployeeStatus;
use App\Models\EmployeeTransportAllowance;
use App\Models\GISEligibility;
use App\Models\HouseRentAllowanceRate;
use App\Models\NetSalary;
use App\Models\NonPracticingAllowanceRate;
use App\Models\PayMatrixCell;
use App\Models\PaySlip;
use App\Models\UniformAllowanceRate;
use Illuminate\Http\Request;

class PaySlipController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = PaySlip::with('addby:id,name,role_id', 'editby:id,name,role_id', 'netSalary.employee');

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
            'employee_id' => 'required|numeric|exists:employees,id',
            'month' => 'required|numeric',
            'year' => 'required|numeric',
            'processing_date' => 'required|date',
            'payment_date' => 'nullable|date',
            'net_amount' => 'required|numeric',
            'employee_bank_id' => 'required|numeric|exists:employee_bank_accounts,id',

            // 'net_salary_id' => 'nullable|numeric|exists:net_salaries,id',
            'pay_structure_id' => 'required|numeric|exists:employee_pay_structures,id',
            'da_rate_id' => 'nullable|numeric|exists:dearnes_allowance_rates,id',
            // 'da_amount' => 'required|numeric',
            'hra_rate_id' => 'nullable|numeric|exists:house_rent_allowance_rates,id',
            // 'hra_amount' => 'required|numeric',
            'npa_rate_id' => 'nullable|numeric|exists:non_practicing_allowance_rates,id',
            // 'npa_amount' => 'required|numeric',
            'transport_rate_id' => 'nullable|numeric|exists:transport_allowance_rates,id',
            // 'transport_amount' => 'required|numeric',
            'uniform_rate_id' => 'nullable|numeric|exists:uniform_allowance_rates,id',
            // 'uniform_rate_amount' => 'nullable|numeric',
            'pay_plus_npa' => 'nullable|numeric',
            'govt_contribution' => 'nullable|numeric',
            'da_on_ta' => 'nullable|numeric',
            'arrears' => 'nullable|numeric',
            'spacial_pay' => 'nullable|numeric',
            'da_1' => 'nullable|numeric',
            'da_2' => 'nullable|numeric',
            'itc_leave_salary' => 'nullable|numeric',
        ]);



        $employeeBank = EmployeeBankAccount::find($request['employee_bank_id']);
        if (!$employeeBank->is_active) return response()->json(['errorMsg' => 'Please fill the correct Bank'], 400);
        if ($employeeBank->employee_id != $request['employee_id']) return response()->json(['errorMsg' => 'Employee Bank not found!'], 404);

        $netSalary = new NetSalary();
        $netSalary->employee_id = $request['employee_id'];
        $netSalary->month = $request['month'];
        $netSalary->year = $request['year'];
        $netSalary->processing_date = $request['processing_date'];
        $netSalary->payment_date = $request['payment_date'];
        $netSalary->net_amount = $request['net_amount'];
        $netSalary->employee_bank_id = $request['employee_bank_id'];
        $netSalary->varified_by = auth()->id();
        $netSalary->added_by = auth()->id();

        try {
            $netSalary->save();

            // return response()->json(['successMsg' => 'Net Salary Created!', 'data' => $netSalary]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }



        $net_salary = $netSalary;
        // if (!$net_salary) return response()->json(['errorMsg' => 'Net Salary not found!'], 404);
        // return response()->json(['Msg' => $net_salary_1]);

        $employee = Employee::find($net_salary->employee_id);
        if (!$employee) return response()->json(['errorMsg' => 'Employee not found!'], 404);

        $employeeStatus = EmployeeStatus::where('employee_id', $employee->id)->orderBy('effective_from', 'DESC')->get();
        $employeeCurrentStatus = $employeeStatus[0];
        // return response()->json(['data' => $employeeCurrentStatus]);


        $taAmount = 0; // Transport Allowance
        $gisAmount = 0;
        // $csmAmount = 0; // Credit society Member
        $npaAmount = 0; // Non Practicing Allowance
        $hraAmount = 0; // House Rent Allowance
        $daRateAmount = 0; // Dearness Allowance Rate
        $uaRateAmount = 0; // Uniform Allowance Rate 
        $totalBasicSalary = 0;

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
        // return response()->json([$employeePayCell, $employeePayLevel, $basicPay]);

        // if ($employeeCurrentStatus->status == 'Active') {

        $taRate = EmployeeTransportAllowance::where('pay_matrix_level', $employeePayLevel->name)->first();
        if (!$taRate) {
            return response()->json(['errorMsg' => 'Transport Allowance not found!'], 404);
        }

        if ($employee->pwd_status) {
            $taAmount += 2 * $taRate->amount;
        } else {
            $taAmount += $taRate->amount;
        }

        // $gisEligibilityAmount = GISEligibility::where('pay_matrix_level', $employeePayLevel->name)->first();
        // if ($employee->gis_eligibility) {
        //     $gisAmount += $gisEligibilityAmount->amount;
        // }

        // if ($employee->credit_society_member) {
        //     if (!$request['credit_society_member_amount']) {
        //         return response()->json(['errorMsg' => 'Credit Society Membership Amount required!'], 400);
        //     }
        // }
        if ($employee->npa_eligibility) {
            $nonPracticingAllowance = NonPracticingAllowanceRate::find($request['npa_rate_id']);
            if (!$nonPracticingAllowance) {
                return response()->json(['errorMsg' => 'Non Practicing Allowance not found!'], 404);
            }
            $npaAmount += $basicPay * ($nonPracticingAllowance->rate_percentage / 100);
        }

        $employeeQuarter = EmployeeQuarter::where('employee_id', $employee->id)->orderBy('date_of_occupation', 'DESC')->first();
        $houseRentAllowance = HouseRentAllowanceRate::find($request['hra_rate_id']);
        if ($employee->hra_eligibility) {
            if (!$houseRentAllowance) {
                return response()->json(['errorMsg' => 'House Rent Allowance not found!'], 404);
            }
            $hraAmount += $basicPay * ($houseRentAllowance->rate_percentage / 100);
        } else {
            if ($employeeQuarter && !$employeeQuarter->is_occupied) {
                if (!$houseRentAllowance) {
                    return response()->json(['errorMsg' => 'House Rent Allowance not found!'], 404);
                }
                $hraAmount += $basicPay * ($houseRentAllowance->rate_percentage / 100);
            }
        }

        // $uaRates = UniformAllowanceRate::find($request['uniform_rate_id'])->orderBy('effective_from', 'DESC');
        // if (!$uaRates) {
        //     return response()->json(['errorMsg' => 'Uniform Allowance Rates not found!']);
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
        // return response()->json(['errorMsg' => $payMatrixCell], 400);
        $dearnessAllowanceRate = DearnesAllowanceRate::find($request['da_rate_id']);
        if (!$dearnessAllowanceRate) {
            return response()->json(['errorMsg' => 'Dearness Allowance Rate not found!'], 404);
        }
        $totalOfBasicPayAndNPA = $basicPay + $npaAmount;
        if ($totalOfBasicPayAndNPA >= $payMatrixCell[0]->amount) {
            $totalOfBasicPayAndNPA = $payMatrixCell[0]->amount;
        }
        $daRateAmount = $totalOfBasicPayAndNPA * ($dearnessAllowanceRate->rate_percentage / 100);
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
        $sum = $request['govt_contribution'] +  $request['pay_plus_npa'] + $request['spacial_pay'] + $request['arrears'] + $request['da_1'] + $request['da_2'] + $request['itc_leave_salary'];
        $totalBasicSalary = $basicPay + $taAmount + $gisAmount + $npaAmount + $hraAmount + $daRateAmount + $uaRateAmount + $sum;

        // return response()->json([
        //     'errorMsg' => 'Employee Pay structure',
        //     'employee' => $employee,
        //     'employeePayStructure' => $employeePayStructure,
        //     'basicPay' => $basicPay,
        //     // 'taRate' => $taRate,
        //     'taAmount' => $taAmount,
        //     // 'gisEligibilityAmount' => $gisEligibilityAmount,
        //     'gisAmount' => $gisAmount,
        //     'npaAmount' => $npaAmount,
        //     'hraAmount' => $hraAmount,
        //     'daRateAmount' => $daRateAmount,
        //     'totalBasicSalary' => $totalBasicSalary,
        // ]);


        $salaryPay = new PaySlip();
        $salaryPay->net_salary_id = $net_salary->id;
        $salaryPay->pay_structure_id = $request['pay_structure_id'];
        $salaryPay->basic_pay = $basicPay;
        $salaryPay->da_rate_id = $request['da_rate_id'];
        $salaryPay->da_amount = $daRateAmount ?? 0;
        $salaryPay->hra_rate_id = $request['hra_rate_id'];
        $salaryPay->hra_amount = $hraAmount ?? 0;
        $salaryPay->npa_rate_id = $request['npa_rate_id'];
        $salaryPay->npa_amount = $npaAmount ?? 0;
        $salaryPay->transport_rate_id = $taRate->id;
        $salaryPay->transport_amount = $taAmount ?? 0;
        $salaryPay->uniform_rate_id = $request['uniform_rate_id'];
        $salaryPay->uniform_rate_amount = $uaRateAmount ?? 0;
        $salaryPay->pay_plus_npa = $request['pay_plus_npa'] ?? 0;
        $salaryPay->govt_contribution = $request['govt_contribution'] ?? 0;
        $salaryPay->da_on_ta = $request['da_on_ta'] ?? 0;
        $salaryPay->arrears = $request['arrears'] ?? 0;
        $salaryPay->spacial_pay = $request['spacial_pay'] ?? 0;
        $salaryPay->da_1 = $request['da_1'] ?? 0;
        $salaryPay->da_2 = $request['da_2'] ?? 0;
        $salaryPay->itc_leave_salary = $request['itc_leave_salary'] ?? 0;
        $salaryPay->total_pay = $totalBasicSalary;
        $salaryPay->added_by = auth()->id();

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
            'pay_structure_id' => 'required|numeric|exists:employee_pay_structures,id',
            'net_salary_id' => 'required|numeric|exists:net_salaries,id',
            'da_rate_id' => 'nullable|numeric|exists:dearnes_allowance_rates,id',
            'hra_rate_id' => 'nullable|numeric|exists:house_rent_allowance_rates,id',
            'npa_rate_id' => 'nullable|numeric|exists:non_practicing_allowance_rates,id',
            'transport_rate_id' => 'nullable|numeric|exists:transport_allowance_rates,id',
            'uniform_rate_id' => 'nullable|numeric|exists:uniform_allowance_rates,id',
            'pay_plus_npa' => 'required|numeric',
            'govt_contribution' => 'required|numeric',
            // 'da_on_ta' => 'required|numeric',
            'arrears' => 'required|numeric',
            'spacial_pay' => 'required|numeric',
            'da_1' => 'required|numeric',
            'da_2' => 'required|numeric',
            'itc_leave_salary' => 'required|numeric',
        ]);

        // $employeeBank = EmployeeBankAccount::find($request['employee_bank_id']);
        // if (!$employeeBank->is_active) return response()->json(['errorMsg' => 'Please fill the correct Bank'], 400);
        // if ($employeeBank->employee_id != $request['employee_id']) return response()->json(['errorMsg' => 'Employee Bank not found!'], 404);


        $net_salary = NetSalary::find($request['net_salary_id']);
        if (!$net_salary) {
            return response()->json(['errorMsg' => 'Net Salary not found!']);
        }

        $employee = Employee::find($net_salary->employee_id);
        if (!$employee) return response()->json(['errorMsg' => 'Employee not found!'], 404);

        $employeeStatus = EmployeeStatus::where('employee_id', $employee->id)->orderBy('effective_from', 'DESC')->get();
        $employeeCurrentStatus = $employeeStatus[0];


        // $taAmount = 0; // Transport Allowance
        // $gisAmount = 0;
        // // $csmAmount = 0; // Credit society Member
        // $npaAmount = 0; // Non Practicing Allowance
        // $hraAmount = 0; // House Rent Allowance
        // $daRateAmount = 0; // Dearness Allowance Rate
        // $uaRateAmount = 0; // Uniform Allowance Rate 
        // $totalBasicSalary = 0;

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

        $taRate = EmployeeTransportAllowance::where('pay_matrix_level', $employeePayLevel->name)->first();
        if (!$taRate) {
            return response()->json(['errorMsg' => 'Transport Allowance not found!'], 404);
        }

        if ($employee->pwd_status) {
            $salaryPay->transport_amount += 2 * $taRate->amount;
        } else {
            $salaryPay->transport_amount += $taRate->amount;
        }

        if ($employee->npa_eligibility) {
            $nonPracticingAllowance = NonPracticingAllowanceRate::find($request['npa_rate_id']);
            if (!$nonPracticingAllowance) {
                return response()->json(['errorMsg' => 'Non Practicing Allowance not found!'], 404);
            }
            $salaryPay->npa_amount += $basicPay * ($nonPracticingAllowance->rate_percentage / 100);
        }

        $employeeQuarter = EmployeeQuarter::where('employee_id', $employee->id)->orderBy('date_of_occupation', 'DESC')->first();
        $houseRentAllowance = HouseRentAllowanceRate::find($request['hra_rate_id']);
        if ($employee->hra_eligibility) {
            if (!$houseRentAllowance) {
                return response()->json(['errorMsg' => 'House Rent Allowance not found!'], 404);
            }
            $salaryPay->hra_amount += $basicPay * ($houseRentAllowance->rate_percentage / 100);
        } else {
            if ($employeeQuarter && !$employeeQuarter->is_occupied) {
                if (!$houseRentAllowance) {
                    return response()->json(['errorMsg' => 'House Rent Allowance not found!'], 404);
                }
                $salaryPay->hra_amount += $basicPay * ($houseRentAllowance->rate_percentage / 100);
            }
        }

        if ($employee->uniform_allowance_eligibility) {
            $uniformAllowance = UniformAllowanceRate::find($request['uniform_rate_id']);
            if (!$uniformAllowance) {
                return response()->json(['errorMsg' => 'Uniform Allowance Rate not found!'], 404);
            }
            $salaryPay->uniform_rate_amount = $uniformAllowance->amount;
        } else {
            $salaryPay->uniform_rate_amount = 0;
        }

        $payMatrixCell = PayMatrixCell::with('payMatrixLevel')->where('index', $employeePayCell->index)->orderBy('amount', 'DESC')->get();
        $dearnessAllowanceRate = DearnesAllowanceRate::find($request['da_rate_id']);
        if (!$dearnessAllowanceRate) {
            return response()->json(['errorMsg' => 'Dearness Allowance Rate not found!'], 404);
        }
        $totalOfBasicPayAndNPA = $basicPay + $salaryPay->npa_amount;
        if ($totalOfBasicPayAndNPA >= $payMatrixCell[0]->amount) {
            $totalOfBasicPayAndNPA = $payMatrixCell[0]->amount;
        }
        $salaryPay->da_amount = $totalOfBasicPayAndNPA * ($dearnessAllowanceRate->rate_percentage / 100);
        $sum = $request['govt_contribution'] +  $request['pay_plus_npa'] + $request['spacial_pay'] + $request['arrears'] + $request['da_1'] + $request['da_2'] + $request['itc_leave_salary'];
        $totalBasicSalary = $basicPay + $salaryPay->transport_amount + $salaryPay->gisAmount + $salaryPay->npa_amount + $salaryPay->hra_amount + $salaryPay->da_amount + $salaryPay->uniform_rate_amount + $sum;


        $salaryPay->net_salary_id = $net_salary->id;
        $salaryPay->pay_structure_id = $request['pay_structure_id'];
        $salaryPay->basic_pay = $basicPay;
        $salaryPay->da_rate_id = $request['da_rate_id'];
        $salaryPay->da_amount; // = $daRateAmount;
        $salaryPay->hra_rate_id = $request['hra_rate_id'];
        $salaryPay->hra_amount; //= $salaryPay->hraAmount;
        $salaryPay->npa_rate_id = $request['npa_rate_id'];
        $salaryPay->npa_amount; //= $npaAmount;
        $salaryPay->transport_rate_id = $taRate->id;
        $salaryPay->transport_amount; // = $salaryPay->taAmount;
        $salaryPay->uniform_rate_id = $request['uniform_rate_id'];
        $salaryPay->uniform_rate_amount; // = $uniform_rate_amount;
        $salaryPay->pay_plus_npa = $request['pay_plus_npa'];
        $salaryPay->govt_contribution = $request['govt_contribution'];
        $salaryPay->da_on_ta = $request['da_on_ta'] ?? 0;
        $salaryPay->arrears = $request['arrears'];
        $salaryPay->spacial_pay = $request['spacial_pay'];
        $salaryPay->da_1 = $request['da_1'];
        $salaryPay->da_2 = $request['da_2'];
        $salaryPay->itc_leave_salary = $request['itc_leave_salary'];
        $salaryPay->total_pay = $totalBasicSalary;
        $salaryPay->added_by = auth()->id();

        try {
            $salaryPay->save();

            return response()->json(['successMsg' => 'Pay Slip updated!', 'data' => $salaryPay]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
