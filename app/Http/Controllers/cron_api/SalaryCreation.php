<?php

namespace App\Http\Controllers\cron_api;

use App\Models\Deduction;
use App\Models\DeductionRecoveries;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\NetSalary;
use App\Models\PaySlip;
use App\Models\SalaryArrears;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    $skippedEmployees = [];

    foreach ($previousMonthSalaries as $previousSalary) {
        // Check if salary for current month already exists
        $existingSalary = NetSalary::where('employee_id', $previousSalary->employee_id)
            ->where('month', $request['month'])
            ->where('year', $request['year'])
            ->first();

        if ($existingSalary) {
            $skippedEmployees[] = $previousSalary->employee_id;
            $errors[] = "Salary for employee ID {$previousSalary->employee_id} already exists for {$request['month']}/{$request['year']}";
            continue;
        }

        try {
            // Validate employee and bank account
            $employee = Employee::find($previousSalary->employee_id);
            if (!$employee) {
                $errors[] = "Employee not found for ID: {$previousSalary->employee_id}";
                continue;
            }

            $user = User::where('is_retired', 1)->find($employee->user_id);
            if ($user) {
                $skippedEmployees[] = "Employee is retired: {$employee->employee_code} - {$employee->name}";
                continue;
            }

            $employeeBank = EmployeeBankAccount::find($previousSalary->employee_bank_id);
            if (!$employeeBank || $employeeBank->employee_id != $employee->id || !$employeeBank->is_active) {
                $errors[] = "Invalid bank account for employee ID: {$previousSalary->employee_id}";
                continue;
            }
            DB::beginTransaction();

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
                $newDeduction->computer_advance = $previousSalary->deduction->computer_advance;
                $newDeduction->computer_advance_installment = $previousSalary->deduction->computer_advance_installment;
                $newDeduction->computer_advance_inst_no = $previousSalary->deduction->computer_advance_inst_no;
                $newDeduction->computer_advance_balance = $previousSalary->deduction->computer_advance_balance;
                $newDeduction->employee_contribution_10 = $previousSalary->deduction->employee_contribution_10;
                $newDeduction->govt_contribution_14_recovery = $previousSalary->deduction->govt_contribution_14_recovery;
                $newDeduction->dies_non_recovery = $previousSalary->deduction->dies_non_recovery;
                $newDeduction->computer_advance_interest = $previousSalary->deduction->computer_advance_interest;
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
            $errors[] = "Error processing employee ID {$previousSalary->employee}: " . $e->getMessage();
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
    if (!empty($skippedEmployees)) {
        $response['skipped_employees'] = $skippedEmployees;
    }

    return response()->json($response);
}
