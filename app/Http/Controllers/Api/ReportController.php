<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\NetSalary;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    function index()
    {
        $total_salary = 0;

        $employees = Employee::where('institute', request('institute'))->get();
        $employeeIds = $employees->pluck('id');

        $netSalary = NetSalary::where('month', request('month'))->where('year', request('year'))->whereIn('employee_id', $employeeIds)->get();

        return response()->json(['employees' => $employees, 'netSalary' => $netSalary]);
    }
}
