<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\EmployeeStatus;
use Illuminate\Support\Facades\DB;

class EmployeeStatusController extends Controller
{
    function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|numeric|exists:employees,id',
            'status' => 'required|in:Active,Suspended,Resigned,Retired,On Leave',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'remarks' => 'nullable|string|max:255',
            'order_reference' => 'nullable|string|max:255',
        ]);

        $isSmallDate = EmployeeStatus::where('employee_id', $request['employee_id'])->where('effective_from', '>=', $request['effective_from'])->get()->first();
        if ($isSmallDate) return response()->json(['errorMsg' => 'Effective From date is smaller than previous!'], 400);
        // if ($request['effective_till'] && $request['effective_till'] <= $request['effective_from']) {
        //     return response()->json(['errorMsg' => 'Effective till must be greater than effective from'], 400);
        // }
        $employee = EmployeeStatus::where('employee_id', $request['employee_id'])->get()->last();
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
        $employeeStatus->added_by = auth()->id();

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
            'effective_till' => 'nullable|date|after:effective_from',
            'remarks' => 'nullable|string|max:255',
            'order_reference' => 'nullable|string|max:255',
        ]);

        $isSmallDate = EmployeeStatus::where('employee_id', $request['employee_id'])->where('effective_from', '>', $request['effective_from'])->get()->first();
        if ($isSmallDate) return response()->json(['errorMsg' => 'Effective From date is smaller than previous!'], 400);

        DB::beginTransaction();

        $old_data = $employeeStatus->toArray();

        $employeeStatus->status = $request['status'];
        $employeeStatus->effective_from = $request['effective_from'];
        $employeeStatus->effective_till = $request['effective_till'];
        $employeeStatus->remarks = $request['remarks'];
        $employeeStatus->order_reference = $request['order_reference'];
        $employeeStatus->edited_by = auth()->id();

        try {
            $employeeStatus->save();

            $employeeStatus->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Employee Status Updated!', 'data' => $employeeStatus]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = EmployeeStatus::query();

        $query->when('employee_id', fn($q) => $q->where('employee_id', 'LIKE', '%' . request('employee_id') . '%'));

        // $query->when(
        //     request('current_status'),
        //     fn($q) => $q->whereHas(
        //         'employeeStatus',
        //         fn($qn) => $qn->where('status', request('current_status'))
        //             ->whereDate('effective_from', '<=', date('Y-m-d'))
        //             ->whereDate('effective_till', '>=', date('Y-m-d'))
        //     )
        // );

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function show($id)
    {
        $data = EmployeeStatus::with('addedBy', 'editedBy', 'history.addedBy', 'history.editedBy', 'employee')->find($id);
        return response()->json(['data' => $data]);
    }
}
