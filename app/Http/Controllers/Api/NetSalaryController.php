<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeBankAccount;
use App\Models\NetSalary;
use Illuminate\Http\Request;

class NetSalaryController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = NetSalary::with('addby:id,name,role_id', 'editby:id,name,role_id', 'varifyby:id,name,role_id', 'deduction', 'paySlip');

        $query->when(
            request('employee_id'),
            fn($q) => $q->where('employee_id', 'LIKE', '%' . request('employee_id') . '%')
        );

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|numeric|exists:employees,id',
            'month' => 'required|numeric',
            'year' => 'required|numeric',
            'processing_date' => 'required|date',
            'payment_date' => 'nullable|date|after:processing_date',
            'net_amount' => 'required|numeric',
            'employee_bank_id' => 'required|numeric|exists:employee_bank_accounts,id',
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

            return response()->json(['successMsg' => 'Net Salary Created!', 'data' => $netSalary]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $netSalary = NetSalary::find($id);
        if (!$netSalary) return response()->json(['errorMsg' => 'Net Salary not found!'], 404);

        $request->validate([
            'employee_id' => 'required|numeric|exists:employees,id',
            'month' => 'required|numeric',
            'year' => 'required|numeric',
            'processing_date' => 'required|date',
            'payment_date' => 'nullable|date|after:processing_date',
            'net_amount' => 'required|numeric',
            'employee_bank_id' => 'required|numeric|exists:employee_bank_accounts,id',
        ]);

        $employeeBank = EmployeeBankAccount::find($request['employee_bank_id']);
        if (!$employeeBank->is_active) return response()->json(['errorMsg' => 'Please fill the correct Bank'], 400);
        if ($employeeBank->employee_id != $request['employee_id']) return response()->json(['errorMsg' => 'Employee Bank not found!'], 404);

        $netSalary->employee_id = $request['employee_id'];
        $netSalary->month = $request['month'];
        $netSalary->year = $request['year'];
        $netSalary->processing_date = $request['processing_date'];
        $netSalary->payment_date = $request['payment_date'];
        $netSalary->net_amount = $request['net_amount'];
        $netSalary->employee_bank_id = $request['employee_bank_id'];
        $netSalary->edited_by = auth()->id();

        try {
            $netSalary->save();

            return response()->json(['successMsg' => 'Net Salary Updated!', 'data' => $netSalary]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
