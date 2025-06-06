<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmployeeBankAccount;
use Illuminate\Support\Facades\DB;

class EmployeeBankController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = EmployeeBankAccount::with('employee:id,first_name,last_name,date_of_joining')->when(
            request('employee_id'),
            fn($q) => $q->where('employee_id', request('employee_id'))
        );

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();
        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function show($id)
    {
        $data = EmployeeBankAccount::with('addedBy', 'editedBy', 'history.editedBy', 'history.addedBy')->find($id);
        return response()->json(['data' => $data]);
    }

    function changeStatus($id)
    {
        $employeeBank = EmployeeBankAccount::find($id);
        if (!$employeeBank) return response()->json(['errorMsg' => 'Employee Bank Not Found!']);

        DB::beginTransaction();

        $old_data = $employeeBank->toArray();

        $employeeBank->is_active = !$employeeBank->is_active;
        $employeeBank->edited_by = auth()->id();

        try {
            $employeeBank->save();

            $employeeBank->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Employee Bank Status Changed!']);
        } catch (\Exception $e) {
            DB::rollBack();
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
                    'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'
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
        $employeeBank->added_by = auth()->id();

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
                    'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'
                ],
                'effective_from' => 'required|date',
                'is_active' => 'required|boolean|in:1,0',
            ]
        );

        DB::beginTransaction();

        $old_data = $employeeBank->toArray();

        $employeeBank->bank_name = $request['bank_name'];
        $employeeBank->branch_name = $request['branch_name'];
        $employeeBank->account_number = $request['account_number'];
        $employeeBank->ifsc_code = $request['ifsc_code'];
        $employeeBank->effective_from = $request['effective_from'];
        $employeeBank->is_active = $request['is_active'];
        $employeeBank->edited_by = auth()->id();

        try {
            $employeeBank->save();

            $employeeBank->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Employee Bank Updated!', 'data' => $employeeBank]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
