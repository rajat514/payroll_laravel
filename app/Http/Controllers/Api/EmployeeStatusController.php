<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\EmployeeStatus;
use PhpParser\Node\Stmt\TryCatch;

class EmployeeStatusController extends Controller
{
    function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|numeric|exists:employees,id',
            'status' => 'required|in:Active,Suspended,Resigned,Retired,On Leave',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date',
            'remarks' => 'nullable|string|max:255',
            'order_reference' => 'nullable|string|max:255',
        ]);

        $employee = EmployeeStatus::where('employee_id', $request['employee_id'])->get()->last();
        // $employeePreviousStatus = $employee[]
        $employee->effective_till = $request['effective_from'];

        try {
            $employee->save();
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }

        $employeeStatus = new EmployeeStatus();
        $employeeStatus->employee_id = $request['employee_id'];
        $employeeStatus->status = $request['status'];
        $employeeStatus->effective_from = $request['effective_from'];
        $employeeStatus->effective_till = $request['effective_till'];
        $employeeStatus->remarks = $request['remarks'];
        $employeeStatus->order_reference = $request['order_reference'];

        try {
            $employeeStatus->save();

            return response()->json([
                'successMsg' => 'Employee Status Created!',
                'data' => $employeeStatus
            ]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $employeeStatus = EmployeeStatus::find($id);
        if (!$employeeStatus) return response()->json(['errorMsg' => 'Employee Status Not Found!']);

        $request->validate([
            'status' => 'required|in:Active,Suspended,Resigned,Retired,On Leave',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date',
            'remarks' => 'nullable|string|max:255',
            'order_reference' => 'nullable|string|max:255',
        ]);

        $employeeStatus->status = $request['status'];
        $employeeStatus->effective_from = $request['effective_from'];
        $employeeStatus->effective_till = $request['effective_till'];
        $employeeStatus->remarks = $request['remarks'];
        $employeeStatus->order_reference = $request['order_reference'];

        try {
            $employeeStatus->save();

            return response()->json([
                'successMsg' => 'Employee Status Updated!',
                'data' => $employeeStatus
            ]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function show($id)
    {
        $data = EmployeeStatus::where('employee_id', $id)->get();
        return response()->json(['data' => $data]);
    }
}
