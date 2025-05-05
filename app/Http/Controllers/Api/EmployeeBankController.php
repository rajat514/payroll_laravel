<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmployeeBankAccount;

class EmployeeBankController extends Controller
{
    function index()
    {
        $query = EmployeeBankAccount::with('employee')->when(
            request('employee_id'),
            fn($q) => $q->where('employee_id', request('employee_id'))
        );
        $data = $query->get();
        return response()->json(['data' => $data]);
    }

    function show($id)
    {
        $data = EmployeeBankAccount::find($id);
        return response()->json(['data' => $data]);
    }

    function changeStatus($id)
    {
        $employeeBank = EmployeeBankAccount::find($id);
        if (!$employeeBank) return response()->json(['errorMsg' => 'Employee Bank Not Found!']);

        $employeeBank->is_active = !$employeeBank->is_active;

        try {
            $employeeBank->save();

            return response()->json(['successMsg' => 'Employee Bank Status Changed!']);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function store(Request $request)
    {
        $request->validate(
            [
                'employee_id' => 'required|numeric|exists:employees,id',
                'bank_name' => 'required|string|min:2|max:191',
                'branch_name' => 'required|string|min:2|max:191',
                'account_number' => 'required|string|max:30',
                'ifsc_code' => [
                    'required',
                    'regex:"^[^\s]{4}\d{7}$"'
                ],
                'effective_from' => 'required|date',
                'is_active' => 'boolean|in:1,0',
            ]
        );

        $employeeBank = new EmployeeBankAccount();
        $employeeBank->employee_id = $request['employee_id'];
        $employeeBank->bank_name = $request['bank_name'];
        $employeeBank->branch_name = $request['branch_name'];
        $employeeBank->account_number = $request['account_number'];
        $employeeBank->ifsc_code = $request['ifsc_code'];
        $employeeBank->effective_from = $request['effective_from'];
        $employeeBank->is_active = $request['is_active'];

        try {
            $employeeBank->save();

            return response()->json(['successMsg' => 'Employee Bank Added!', 'data' => $employeeBank]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
    function update(Request $request, $id)
    {
        $employeeBank = EmployeeBankAccount::find($id);
        if (!$employeeBank) return response()->json(['errorMsg' => 'Employee Bank Not Found!'], 404);

        $request->validate(
            [
                'bank_name' => 'required|string|min:2|max:191',
                'branch_name' => 'required|string|min:2|max:191',
                'account_number' => 'required|string|max:30',
                'ifsc_code' => [
                    'required',
                    'regex:"^[^\s]{4}\d{7}$"'
                ],
                'effective_from' => 'required|date',
                'is_active' => 'required|boolean|in:1,0',
            ]
        );

        $employeeBank->bank_name = $request['bank_name'];
        $employeeBank->branch_name = $request['branch_name'];
        $employeeBank->account_number = $request['account_number'];
        $employeeBank->ifsc_code = $request['ifsc_code'];
        $employeeBank->effective_from = $request['effective_from'];
        $employeeBank->is_active = $request['is_active'];

        try {
            $employeeBank->save();

            return response()->json(['successMsg' => 'Employee Bank Updated!', 'data' => $employeeBank]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
