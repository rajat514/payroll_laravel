<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeBankAccount;
use App\Models\NetSalary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NetSalaryController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = NetSalary::with('addedBy', 'editedBy', 'verifiedBy:id,name,role_id', 'deduction', 'paySlip');

        $query->when(
            request('employee_id'),
            fn($q) => $q->where('employee_id', request('employee_id'))
        );

        $query->when(
            request('month'),
            fn($q) => $q->where('month', request('month'))
        );

        $query->when(
            request('year'),
            fn($q) => $q->where('year', request('year'))
        );

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        // $request->validate([
        //     'employee_id' => 'required|numeric|exists:employees,id',
        //     'month' => 'required|numeric',
        //     'year' => 'required|numeric',
        //     'processing_date' => 'required|date',
        //     'payment_date' => 'nullable|date|after:processing_date',
        //     'net_amount' => 'required|numeric',
        //     'employee_bank_id' => 'required|numeric|exists:employee_bank_accounts,id',
        // ]);

        // $employeeBank = EmployeeBankAccount::find($request['employee_bank_id']);
        // if (!$employeeBank->is_active) return response()->json(['errorMsg' => 'Please fill the correct Bank'], 400);
        // if ($employeeBank->employee_id != $request['employee_id']) return response()->json(['errorMsg' => 'Employee Bank not found!'], 404);

        // $netSalary = new NetSalary();
        // $netSalary->employee_id = $request['employee_id'];
        // $netSalary->month = $request['month'];
        // $netSalary->year = $request['year'];
        // $netSalary->processing_date = $request['processing_date'];
        // $netSalary->payment_date = $request['payment_date'];
        // $netSalary->net_amount = $request['net_amount'];
        // $netSalary->employee_bank_id = $request['employee_bank_id'];
        // $netSalary->varified_by = auth()->id();
        // $netSalary->added_by = auth()->id();

        // try {
        //     $netSalary->save();

        //     return response()->json(['successMsg' => 'Net Salary Created!', 'data' => $netSalary]);
        // } catch (\Exception $e) {
        //     return response()->json(['errorMsg' => $e->getMessage()], 500);
        // }
    }

    function update(Request $request, $id)
    {
        $netSalary = NetSalary::find($id);
        if (!$netSalary) return response()->json(['errorMsg' => 'Net Salary not found!'], 404);

        $request->validate([
            'employee_id' => 'required|numeric|exists:employees,id',
            'month' => 'required|numeric|max:12|min:1',
            'year' => 'required|numeric|digits:4|min:1900',
            'processing_date' => 'required|date',
            'payment_date' => 'nullable|date|after:processing_date',
            'employee_bank_id' => 'required|numeric|exists:employee_bank_accounts,id',
        ]);


        $employeeBank = EmployeeBankAccount::find($request['employee_bank_id']);
        if (!$employeeBank->is_active) return response()->json(['errorMsg' => 'Please fill the correct Bank'], 400);
        if ($employeeBank->employee_id != $request['employee_id']) return response()->json(['errorMsg' => 'Employee Bank not found!'], 404);

        DB::beginTransaction();

        $old_data = $netSalary->toArray();

        $netSalary->employee_id = $request['employee_id'];
        $netSalary->month = $request['month'];
        $netSalary->year = $request['year'];
        $netSalary->processing_date = $request['processing_date'];
        $netSalary->payment_date = $request['payment_date'];
        // $netSalary->net_amount = $request['net_amount'];
        $netSalary->employee_bank_id = $request['employee_bank_id'];
        $netSalary->edited_by = auth()->id();

        try {
            $netSalary->save();

            $netSalary->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Net Salary Updated!', 'data' => $netSalary]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function show($id)
    {
        $netSalary = NetSalary::with(
            'employee.employeeDesignation',
            'deduction',
            'paySlip',
            'verifiedBy',
            'history.verifiedBy',
            'addedBy',
            'editedBy',
            'history.addedBy',
            'history.editedBy'
        )->find($id);

        return response()->json(['data' => $netSalary]);
    }

    function verifySalary(Request $request)
    {
        $data = $request['selected_id'];
        if (!is_array($data)) {
            $data = [$data];
        }

        foreach ($data as $index) {
            $netSalary = NetSalary::find($index);
            if (!$netSalary) return response()->json(['errorMsg' => 'Net Salary not found!'], 404);

            $netSalary->is_verified = 1;
            $netSalary->verified_by = auth()->id();

            try {
                $netSalary->save();
            } catch (\Exception $e) {
                return response()->json(['errorMsg' => $e->getMessage()], 500);
            }
        }

        return response()->json(['data' => 'Salary varified successfully!']);
    }
}
