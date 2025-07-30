<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\NetPension;
use App\Models\NetSalary;
use App\Models\PensionerInformation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private \App\Models\User $user;

    function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = \App\Models\User::find(auth()->id());
            return $next($request);
        });
    }

    function index()
    {
        // $total_salary = 0;

        // $employees = Employee::where('institute', request('institute'))->get();
        // $employeeIds = $employees->pluck('id');

        // $netSalary = NetSalary::where('month', request('month'))->where('year', request('year'))->whereIn('employee_id', $employeeIds)->get();
        $query = NetSalary::with('employee', 'employeeBank', 'paySlip', 'deduction');

        $query->when(
            request('institute'),
            fn($q) => $q->whereHas('employee', fn($qn) => $qn->where('institute', request('institute')))
        );

        $query->when(
            request('month'),
            fn($q) => $q->where('month', request('month'))
        );

        $query->when(
            request('year'),
            fn($q) => $q->where('year', request('year'))
        );

        $query->when(
            request('employee_id'),
            fn($q) => $q->where('employee_id', request('employee_id'))
        );

        $netSalary = $query->get();

        return response()->json(['data' => $netSalary]);
    }

    function bankStatement()
    {
        $netSalary = NetSalary::with([
            'employee:id,employee_code,prefix,first_name,middle_name,last_name',
            'employeeBank:id,employee_id,account_number',
            'employee.latestEmployeeDesignation:id,employee_id,designation' // Load only latest designation
        ])
            ->where('month', request('month'))
            ->where('year', request('year'))
            ->select('id', 'employee_id', 'employee_bank_id', 'month', 'year', 'net_amount')
            ->get();

        return response()->json(['data' => $netSalary]);
    }

    function lic()
    {
        $netSalary = NetSalary::with([
            'employee:id,employee_code,prefix,first_name,middle_name,last_name',
            'deduction:id,net_salary_id,lic'
        ])
            ->where('month', request('month'))
            ->where('year', request('year'))
            ->select('id', 'employee_id', 'month', 'year', 'net_amount')
            ->get();

        return response()->json(['data' => $netSalary]);
    }

    function gis()
    {
        $netSalary = NetSalary::with([
            'employee:id,employee_code,prefix,first_name,middle_name,last_name,date_of_birth,date_of_joining',
            'employee.latestEmployeeDesignation:id,employee_id,designation',
            'deduction:id,net_salary_id,gis'
        ])
            ->where('month', request('month'))
            ->where('year', request('year'))
            ->select('id', 'employee_id', 'month', 'year', 'net_amount')
            ->get();

        return response()->json(['data' => $netSalary]);
    }

    function newPensionScheme()
    {
        $netSalary = NetSalary::with([
            'employee:id,employee_code,prefix,first_name,middle_name,last_name,pension_scheme',
            'employee.latestEmployeeDesignation:id,employee_id,designation',
            'paySlip:id,net_salary_id,basic_pay,npa_amount,da_amount',
            'deduction:id,net_salary_id,govt_contribution_14_recovery,employee_contribution_10'
        ])
            ->where('month', request('month'))
            ->where('year', request('year'))
            ->whereHas('employee', function ($query) {
                $query->where('pension_scheme', 'NPS'); // Adjust based on your database values
            })
            ->select('id', 'employee_id', 'month', 'year')
            ->get()
            // ->map(function ($item) {
            //     // Add custom field: basic_with_npa = basic_pay + npa_amount
            //     $item->pay_plus_npa = ($item->paySlip->basic_pay ?? 0) + ($item->paySlip->npa_amount ?? 0);
            //     $item->total = ($item->pay_plus_npa ?? 0) + ($item->paySlip->da_amount ?? 0);
            //     $item->total_contribution = ($item->deduction->govt_contribution_14_recovery ?? 0) + ($item->deduction->employee_contribution_10 ?? 0);
            //     return $item;
            // })
        ;

        return response()->json(['data' => $netSalary]);
    }

    function GPF()
    {
        $netSalary = NetSalary::with([
            'employee:id,employee_code,prefix,first_name,middle_name,last_name,pension_scheme',
            'employee.latestEmployeeDesignation:id,employee_id,designation',
            'paySlip:id,net_salary_id,basic_pay,npa_amount,da_amount',
            'deduction:id,net_salary_id,gpf'
        ])
            ->where('month', request('month'))
            ->where('year', request('year'))
            ->whereHas('employee', function ($query) {
                $query->where('pension_scheme', 'GPF'); // Adjust based on your database values
            })
            ->select('id', 'employee_id', 'month', 'year')
            ->get()
            // ->map(function ($item) {
            //     // Add custom field: basic_with_npa = basic_pay + npa_amount
            //     $item->pay_plus_npa = ($item->paySlip->basic_pay ?? 0) + ($item->paySlip->npa_amount ?? 0);
            //     $item->total = ($item->pay_plus_npa ?? 0) + ($item->paySlip->da_amount ?? 0);
            //     return $item;
            // })
        ;

        return response()->json(['data' => $netSalary]);
    }

    function licenseFee()
    {
        $netSalary = NetSalary::with([
            'employee:id,employee_code,prefix,first_name,middle_name,last_name,pension_scheme',
            'employee.latestEmployeeDesignation:id,employee_id,designation',
            // 'paySlip:id,net_salary_id,basic_pay,npa_amount,da_amount',
            'deduction:id,net_salary_id,license_fee'
        ])
            ->where('month', request('month'))
            ->where('year', request('year'))
            ->select('id', 'employee_id', 'month', 'year')
            ->get()
            // ->map(function ($item) {
            //     // Add custom field: basic_with_npa = basic_pay + npa_amount
            //     $item->pay_plus_npa = ($item->paySlip->basic_pay ?? 0) + ($item->paySlip->npa_amount ?? 0);
            //     $item->total = ($item->pay_plus_npa ?? 0) + ($item->paySlip->da_amount ?? 0);
            //     return $item;
            // })
        ;

        return response()->json(['data' => $netSalary]);
    }

    function professionalTax()
    {
        $netSalary = NetSalary::with([
            'employee:id,employee_code,prefix,first_name,middle_name,last_name,pension_scheme',
            'employee.latestEmployeeDesignation:id,employee_id,designation',
            // 'paySlip:id,net_salary_id,basic_pay',
            'deduction:id,net_salary_id,professional_tax'
        ])
            ->where('month', request('month'))
            ->where('year', request('year'))
            ->whereHas('employee', function ($query) {
                $query->where('pwd_status', 1); // Adjust based on your database values
            })
            ->select('id', 'employee_id', 'month', 'year')
            ->get()
            // ->map(function ($item) {
            //     // Add custom field: basic_with_npa = basic_pay + npa_amount
            //     $item->pay_plus_npa = ($item->paySlip->basic_pay ?? 0) + ($item->paySlip->npa_amount ?? 0);
            //     $item->total = ($item->pay_plus_npa ?? 0) + ($item->paySlip->da_amount ?? 0);
            //     return $item;
            // })
        ;

        return response()->json(['data' => $netSalary]);
    }

    function allData()
    {
        $netSalary = NetSalary::with('deduction')->whereHas(
            'deduction',
            fn($qn) => $qn->where('license_fee', "!=", 0)
        )->get();

        return response()->json(['data' => $netSalary]);
    }

    function dashBoardCount()
    {
        $total_employees = Employee::count();
        $total_users = User::count();
        $total_nioh_employees = Employee::where('institute', 'NIOH')->count();
        $total_rohc_employees = Employee::where('institute', 'ROHC')->count();
        $total_pensioners = PensionerInformation::count();

        return response()->json([
            'total_employees' => $total_employees,
            'total_nioh_employees' => $total_nioh_employees,
            'total_rohc_employees' => $total_rohc_employees,
            'total_pensioners' => $total_pensioners,
            'total_users' => $total_users
        ]);
    }

    function dashBoardReports()
    {
        $now = Carbon::now();

        $previousMonth = $now->copy()->subMonth()->month;
        $previousMonthYear = $now->copy()->subMonth()->year;

        $year = request('year');
        $month = request('month');


        $netSalaries = NetSalary::with('paySlip', 'deduction')
            ->where('year', $year ?? $previousMonthYear)
            ->where('month', $month ?? $previousMonth)
            ->when(
                $this->user->institute !== 'BOTH',
                fn($q) => $q->whereHas(
                    'employee',
                    fn($qn) => $qn->where('institute', $this->user->institute)
                )
            )
            ->get();

        $total_basic_pay = $netSalaries->sum(function ($salary) {
            return $salary->paySlip->basic_pay ?? 0;
        });
        $total_income_tax = $netSalaries->sum(function ($salary) {
            return $salary->deduction->income_tax ?? 0;
        });

        $total_net_pay = $netSalaries->sum('net_amount');

        $net_pension = NetPension::with('monthlyPension', 'pensionerDeduction')
            ->where('year', $year ?? $previousMonthYear)
            ->where('month', $month ?? $previousMonth)
            ->when(
                $this->user->institute !== 'BOTH',
                fn($q) => $q->whereHas(
                    'pensioner',
                    fn($p) => $p->whereHas(
                        'employee',
                        fn($e) => $e->where('institute', $this->user->institute)
                    )
                )
            )
            ->get();

        $total_net_pension = $net_pension->sum('net_pension');

        return response()->json([
            'total_income_tax' => $total_income_tax,
            'total_net_pay' => $total_net_pay,
            'total_net_pension' => $total_net_pension
        ]);
    }
}
