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
            fn($q) => $q->where('net_salary_id', 'LIKE', '%' . request('net_salary_id') . '%')
        );

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'ASC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function show($id)
    {
        $data = PaySlip::with('addedBy', 'editedBy', 'history.addedBy', 'history.editedBy', 'netSalary.employee')->find($id);

        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        $taAmount = 0; // Transport Allowance
        $gisAmount = 0;
        // $csmAmount = 0; // Credit society Member
        $npaAmount = 0; // Non Practicing Allowance
        $hraAmount = 0; // House Rent Allowance
        $daRateAmount = 0; // Dearness Allowance Rate
        $uaRateAmount = 0; // Uniform Allowance Rate 
        $totalBasicSalary = 0;
        $netAmount = 0;

        $request->validate([
            'employee_id' => 'required|numeric|exists:employees,id',
            'month' => 'required|numeric|max:12|min:1',
            'year' => 'required|numeric|digits:4|min:1900',
            'processing_date' => 'required|date',
            'payment_date' => 'nullable|date',
            'employee_bank_id' => 'required|numeric|exists:employee_bank_accounts,id',

            'net_salary_id' => 'nullable|numeric|exists:net_salaries,id',
            'pay_structure_id' => 'required|numeric|exists:employee_pay_structures,id',
            'da_rate_id' => 'required|numeric|exists:dearnes_allowance_rates,id',
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

        $employee = Employee::find($request['employee_id']);
        if (!$employee) return response()->json(['errorMsg' => 'Employee not found!'], 404);

        $employeeBank = EmployeeBankAccount::find($request['employee_bank_id']);
        if (!$employeeBank) return response()->json(['errorMsg' => 'Employee Bank not found!'], 404);
        if (!$employeeBank->is_active) return response()->json(['errorMsg' => 'Please fill the correct Bank'], 400);

        $employeePayStructure = EmployeePayStructure::with('payMatrixCell.payMatrixLevel')->find($request['pay_structure_id']);
        if (!$employeePayStructure) {
            return response()->json(['errorMsg' => 'Employee Pay Structure not found!'], 404);
        }
        $employeePayCell = $employeePayStructure->payMatrixCell;
        $employeePayLevel = $employeePayStructure->payMatrixCell->payMatrixLevel;
        $basicPay = $employeePayStructure->PayMatrixCell->amount;

        $taRate = EmployeeTransportAllowance::where('pay_matrix_level', $employeePayLevel->name)->first();
        if (!$taRate) {
            return response()->json(['errorMsg' => 'Transport Allowance not found!'], 404);
        }

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

        if ($employee->uniform_allowance_eligibility) {
            $uniformAllowance = UniformAllowanceRate::find($request['uniform_rate_id']);
            if (!$uniformAllowance) {
                return response()->json(['errorMsg' => 'Uniform Allowance Rate not found!'], 404);
            }
            $uaRateAmount = $uniformAllowance->amount;
        } else {
            $uaRateAmount = 0;
        }

        $dearnessAllowanceRate = DearnesAllowanceRate::find($request['da_rate_id']);
        if (!$dearnessAllowanceRate) {
            return response()->json(['errorMsg' => 'Dearness Allowance Rate not found!'], 404);
        }

        $old_net_salary = NetSalary::where('employee_id', $request['employee_id'])->where('month', $request['month'])->where('year', $request['year'])->get()->first();
        if ($old_net_salary) return response()->json(['errorMsg' => 'This month salary is already generated!'], 400);

        DB::beginTransaction();

        $netSalary = new NetSalary();
        $netSalary->employee_id = $request['employee_id'];
        $netSalary->month = $request['month'];
        $netSalary->year = $request['year'];
        $netSalary->processing_date = $request['processing_date'];
        $netSalary->payment_date = $request['payment_date'];
        $netSalary->net_amount = 0;
        $netSalary->employee_bank_id = $request['employee_bank_id'];
        $netSalary->verified_by = auth()->id();
        $netSalary->added_by = auth()->id();

        try {
            $netSalary->save();

            // return response()->json(['successMsg' => 'Net Salary Created!', 'data' => $netSalary]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }

        $net_salary = $netSalary;

        $employeeStatus = EmployeeStatus::where('employee_id', $employee->id)->orderBy('effective_from', 'DESC')->get();
        $employeeCurrentStatus = $employeeStatus[0];



        if ($net_salary->employee_id != $employeePayStructure->employee_id) {
            DB::rollBack();
            return response()->json(['errorMsg' => 'Employee Net Salary and Employee Pay Structure not matched!']);
        }

        // if ($employeeCurrentStatus->status == 'Active') {

        if ($employee->pwd_status) {
            $taAmount += 2 * $taRate->amount;
        } else {
            $taAmount += $taRate->amount;
        }

        // $uaRates = UniformAllowanceRate::find($request['uniform_rate_id'])->orderBy('effective_from', 'DESC');
        // if (!$uaRates) {
        //     return response()->json(['errorMsg' => 'Uniform Allowance Rates not found!']);
        // }


        $payMatrixCell = PayMatrixCell::with('payMatrixLevel')->where('index', $employeePayCell->index)->orderBy('amount', 'DESC')->get();
        // return response()->json(['errorMsg' => $payMatrixCell], 400);

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

        $net_salary->net_amount = $totalBasicSalary;
        try {
            $net_salary->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }


        $salaryPay = new PaySlip();
        $salaryPay->net_salary_id = $net_salary->id;
        $salaryPay->pay_structure_id = $request['pay_structure_id'];
        $salaryPay->basic_pay = $basicPay;
        $salaryPay->da_rate_id = $request['da_rate_id'];
        $salaryPay->da_amount = $daRateAmount;
        $salaryPay->hra_rate_id = $employee->hra_eligibility ? $request['hra_rate_id'] : null;
        $salaryPay->hra_amount = $hraAmount;
        $salaryPay->npa_rate_id = $employee->npa_eligibility ? $request['npa_rate_id'] : null;
        $salaryPay->npa_amount = $npaAmount;
        $salaryPay->transport_rate_id = $taRate->id;
        $salaryPay->transport_amount = $taAmount;
        $salaryPay->uniform_rate_id = $employee->uniform_allowance_eligibility ? $request['uniform_rate_id'] : null;
        $salaryPay->uniform_rate_amount = $uaRateAmount;
        $salaryPay->pay_plus_npa = $request['pay_plus_npa'];
        $salaryPay->govt_contribution = $request['govt_contribution'];
        $salaryPay->da_on_ta = $request['da_on_ta'];
        $salaryPay->arrears = $request['arrears'];
        $salaryPay->spacial_pay = $request['spacial_pay'];
        $salaryPay->da_1 = $request['da_1'];
        $salaryPay->da_2 = $request['da_2'];
        $salaryPay->itc_leave_salary = $request['itc_leave_salary'];
        $salaryPay->total_pay = $totalBasicSalary;
        $salaryPay->added_by = auth()->id();

        try {
            $salaryPay->save();
            DB::commit();
            return response()->json(['successMsg' => 'Pay Slip created!', 'data' => $salaryPay]);
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
            'pay_structure_id' => 'required|numeric|exists:employee_pay_structures,id',
            // 'net_salary_id' => 'required|numeric|exists:net_salaries,id',
            'da_rate_id' => 'nullable|numeric|exists:dearnes_allowance_rates,id',
            'hra_rate_id' => 'nullable|numeric|exists:house_rent_allowance_rates,id',
            'npa_rate_id' => 'nullable|numeric|exists:non_practicing_allowance_rates,id',
            'transport_rate_id' => 'nullable|numeric|exists:transport_allowance_rates,id',
            'uniform_rate_id' => 'nullable|numeric|exists:uniform_allowance_rates,id',
            'pay_plus_npa' => 'nullable|numeric',
            'govt_contribution' => 'nullable|numeric',
            // 'da_on_ta' => 'required|numeric',
            'arrears' => 'nullable|numeric',
            'spacial_pay' => 'nullable|numeric',
            'da_1' => 'nullable|numeric',
            'da_2' => 'nullable|numeric',
            'itc_leave_salary' => 'nullable|numeric',
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

        $employeeStatus = EmployeeStatus::where('employee_id', $employee->id)->orderBy('effective_from', 'DESC')->get();
        $employeeCurrentStatus = $employeeStatus[0];


        $taAmount = 0; // Transport Allowance
        // $gisAmount = 0;
        // // $csmAmount = 0; // Credit society Member
        $npaAmount = 0; // Non Practicing Allowance
        $hraAmount = 0; // House Rent Allowance
        $daRateAmount = 0; // Dearness Allowance Rate
        $uaRateAmount = 0; // Uniform Allowance Rate 
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
            $taAmount += 2 * $taRate->amount;
        } else {
            $taAmount += $taRate->amount;
        }

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
        if ($totalOfBasicPayAndNPA >= $payMatrixCell[0]->amount) {
            $totalOfBasicPayAndNPA = $payMatrixCell[0]->amount;
        }
        $daRateAmount = $totalOfBasicPayAndNPA * ($dearnessAllowanceRate->rate_percentage / 100);
        $sum = $request['govt_contribution'] +  $request['pay_plus_npa'] + $request['spacial_pay'] + $request['arrears'] + $request['da_1'] + $request['da_2'] + $request['itc_leave_salary'];
        $totalBasicSalary = $basicPay + $taAmount + $npaAmount + $hraAmount + $salaryPay->da_amount + $uaRateAmount + $sum;

        DB::beginTransaction();

        $old_data = $salaryPay->toArray();

        // $salaryPay->net_salary_id = $net_salary->id;
        $salaryPay->pay_structure_id = $request['pay_structure_id'];
        $salaryPay->basic_pay = $basicPay;
        $salaryPay->da_rate_id = $request['da_rate_id'];
        $salaryPay->da_amount = $daRateAmount;
        $salaryPay->hra_rate_id = $request['hra_rate_id'];
        $salaryPay->hra_amount = $hraAmount;
        $salaryPay->npa_rate_id = $request['npa_rate_id'];
        $salaryPay->npa_amount = $npaAmount;
        $salaryPay->transport_rate_id = $taRate->id;
        $salaryPay->transport_amount = $taAmount;
        $salaryPay->uniform_rate_id = $request['uniform_rate_id'];
        $salaryPay->uniform_rate_amount = $uaRateAmount;
        $salaryPay->pay_plus_npa = $request['pay_plus_npa'];
        $salaryPay->govt_contribution = $request['govt_contribution'];
        $salaryPay->da_on_ta = $request['da_on_ta'];
        $salaryPay->arrears = $request['arrears'];
        $salaryPay->spacial_pay = $request['spacial_pay'];
        $salaryPay->da_1 = $request['da_1'];
        $salaryPay->da_2 = $request['da_2'];
        $salaryPay->itc_leave_salary = $request['itc_leave_salary'];
        $salaryPay->total_pay = $totalBasicSalary;
        $salaryPay->added_by = auth()->id();

        try {
            $salaryPay->save();

            if ($net_salary->deduction) {
                $net_salary->net_amount = $salaryPay->total_pay - $net_salary->deduction->total_deductions;
            }

            $net_salary->save();

            $salaryPay->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Pay Slip updated!', 'data' => $salaryPay]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function bulkStore(Request $request)
    {
        $taAmount = 0; // Transport Allowance
        $gisAmount = 0;
        // $csmAmount = 0; // Credit society Member
        $npaAmount = 0; // Non Practicing Allowance
        $hraAmount = 0; // House Rent Allowance
        $daRateAmount = 0; // Dearness Allowance Rate
        $uaRateAmount = 0; // Uniform Allowance Rate 
        $totalBasicSalary = 0;
        $netAmount = 0;

        $request->validate([
            'month' => 'required|numeric|max:12|min:1',
            'year' => 'required|numeric|digits:4|min:1900',
            'processing_date' => 'required|date',
            'payment_date' => 'nullable|date',
        ]);

        $previousMonth = $request['month'];
        $previousYear = $request['year'];
        if ($request['month'] == 1) {
            $previousMonth = 13;
            $previousYear -= 1;
        }

        $lastVerifiedEmployee = NetSalary::with('paySlip')->where('month', $previousMonth - 1)->where('year', $previousYear)->get(); //->where('is_verified', 1)

        // if ($lastVerifiedEmployee) {
        //     return response()->json(['errorMsg' => 'No employee found for this requirement!'], 404);
        // }
        foreach ($lastVerifiedEmployee as $employeeData) {
            // return response()->json(['data', $employeeData]);

            $employee = Employee::find($employeeData->employee_id);
            if (!$employee) return response()->json(['errorMsg' => 'Employee not found!'], 404);

            $employeeBank = EmployeeBankAccount::find($employeeData->employee_bank_id);
            if ($employeeBank->employee_id != $employee->id) return response()->json(['errorMsg' => 'This Bank is not related to this employee!'], 400);
            if (!$employeeBank) return response()->json(['errorMsg' => 'Employee Bank not found!'], 404);
            if (!$employeeBank->is_active) return response()->json(['errorMsg' => 'Please fill the correct Bank'], 400);

            $employeePayStructure = EmployeePayStructure::with('payMatrixCell.payMatrixLevel')->find($employeeData->paySlip->pay_structure_id);
            if (!$employeePayStructure) {
                return response()->json(['errorMsg' => 'Employee Pay Structure not found!'], 404);
            }
            $employeePayCell = $employeePayStructure->payMatrixCell;
            $employeePayLevel = $employeePayStructure->payMatrixCell->payMatrixLevel;
            $basicPay = $employeePayStructure->PayMatrixCell->amount;

            $taRate = EmployeeTransportAllowance::where('pay_matrix_level', $employeePayLevel->name)->first();
            if (!$taRate) {
                return response()->json(['errorMsg' => 'Transport Allowance not found!'], 404);
            }

            if ($employee->npa_eligibility) {
                $nonPracticingAllowance = NonPracticingAllowanceRate::find($employeeData->paySlip->npa_rate_id);
                if (!$nonPracticingAllowance) {
                    return response()->json(['errorMsg' => 'Non Practicing Allowance not found!'], 404);
                }
                $npaAmount += $basicPay * ($nonPracticingAllowance->rate_percentage / 100);
            }

            $employeeQuarter = EmployeeQuarter::where('employee_id', $employee->id)->orderBy('date_of_occupation', 'DESC')->first();
            $houseRentAllowance = HouseRentAllowanceRate::find($employeeData->paySlip->hra_rate_id);
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

            if ($employee->uniform_allowance_eligibility) {
                $uniformAllowance = UniformAllowanceRate::find($employeeData->paySlip->uniform_rate_id);
                if (!$uniformAllowance) {
                    return response()->json(['errorMsg' => 'Uniform Allowance Rate not found!'], 404);
                }
                $uaRateAmount = $uniformAllowance->amount;
            } else {
                $uaRateAmount = 0;
            }

            $dearnessAllowanceRate = DearnesAllowanceRate::find($employeeData->paySlip->da_rate_id);
            if (!$dearnessAllowanceRate) {
                return response()->json(['errorMsg' => 'Dearness Allowance Rate not found!'], 404);
            }

            $old_net_salary = NetSalary::where('employee_id', $employeeData->employee_id)->where('month', $request['month'])->where('year', $request['year'])->get()->first();
            if ($old_net_salary) return response()->json(['errorMsg' => 'This month salary is already generated!'], 400);

            DB::beginTransaction();

            $netSalary = new NetSalary();
            $netSalary->employee_id = $employeeData->employee_id;
            $netSalary->month = $request['month'];
            $netSalary->year = $request['year'];
            $netSalary->processing_date = $request['processing_date'];
            $netSalary->payment_date = $request['payment_date'];
            $netSalary->net_amount = 0;
            $netSalary->employee_bank_id = $employeeData->employee_bank_id;
            $netSalary->verified_by = auth()->id();
            $netSalary->added_by = auth()->id();

            try {
                $netSalary->save();

                // return response()->json(['successMsg' => 'Net Salary Created!', 'data' => $netSalary]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['errorMsg' => $e->getMessage()], 500);
            }

            $net_salary = $netSalary;

            $employeeStatus = EmployeeStatus::where('employee_id', $employee->id)->orderBy('effective_from', 'DESC')->get();
            $employeeCurrentStatus = $employeeStatus[0];



            if ($net_salary->employee_id != $employeePayStructure->employee_id) {
                DB::rollBack();
                return response()->json(['errorMsg' => 'Employee Net Salary and Employee Pay Structure not matched!']);
            }

            // if ($employeeCurrentStatus->status == 'Active') {

            if ($employee->pwd_status) {
                $taAmount += 2 * $taRate->amount;
            } else {
                $taAmount += $taRate->amount;
            }

            $payMatrixCell = PayMatrixCell::with('payMatrixLevel')->where('index', $employeePayCell->index)->orderBy('amount', 'DESC')->get();

            $totalOfBasicPayAndNPA = $basicPay + $npaAmount;
            if ($totalOfBasicPayAndNPA >= $payMatrixCell[0]->amount) {
                $totalOfBasicPayAndNPA = $payMatrixCell[0]->amount;
            }
            $daRateAmount = $totalOfBasicPayAndNPA * ($dearnessAllowanceRate->rate_percentage / 100);
            $sum = $employeeData->paySlip->govt_contribution +  $employeeData->paySlip->pay_plus_npa + $employeeData->paySlip->spacial_pay + $employeeData->paySlip->arrears + $employeeData->paySlip->da_1 + $employeeData->paySlip->da_2 + $employeeData->paySlip->itc_leave_salary;
            $totalBasicSalary = $basicPay + $taAmount + $gisAmount + $npaAmount + $hraAmount + $daRateAmount + $uaRateAmount + $sum;

            $net_salary->net_amount = $totalBasicSalary;
            try {
                $net_salary->save();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['errorMsg' => $e->getMessage()], 500);
            }


            $salaryPay = new PaySlip();
            $salaryPay->net_salary_id = $net_salary->id;
            $salaryPay->pay_structure_id = $employeeData->paySlip->pay_structure_id;
            $salaryPay->basic_pay = $basicPay;
            $salaryPay->da_rate_id = $employeeData->paySlip->da_rate_id;
            $salaryPay->da_amount = $daRateAmount;
            $salaryPay->hra_rate_id = $employeeData->paySlip->hra_rate_id;
            $salaryPay->hra_amount = $hraAmount;
            $salaryPay->npa_rate_id = $employeeData->paySlip->npa_rate_id;
            $salaryPay->npa_amount = $npaAmount;
            $salaryPay->transport_rate_id = $taRate->id;
            $salaryPay->transport_amount = $taAmount;
            $salaryPay->uniform_rate_id = $employeeData->paySlip->uniform_rate_id;
            $salaryPay->uniform_rate_amount = $uaRateAmount;
            $salaryPay->pay_plus_npa = $employeeData->paySlip->pay_plus_npa;
            $salaryPay->govt_contribution = $employeeData->paySlip->govt_contribution;
            $salaryPay->da_on_ta = $employeeData->paySlip->da_on_ta;
            $salaryPay->arrears = $employeeData->paySlip->arrears;
            $salaryPay->spacial_pay = $employeeData->paySlip->spacial_pay;
            $salaryPay->da_1 = $employeeData->paySlip->da_1;
            $salaryPay->da_2 = $employeeData->paySlip->da_2;
            $salaryPay->itc_leave_salary = $employeeData->paySlip->itc_leave_salary;
            $salaryPay->total_pay = $totalBasicSalary;
            $salaryPay->added_by = auth()->id();

            try {
                $salaryPay->save();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['errorMsg' => $e->getMessage()], 500);
            }
        }
        return response()->json(['successMsg' => 'salary generated!']);
    }
}
