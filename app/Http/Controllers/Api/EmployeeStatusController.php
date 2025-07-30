<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\EmployeeStatus;
use App\Models\User;
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

        $lastStatus = EmployeeStatus::where('employee_id', $request['employee_id'])->get()->last();
        if ($lastStatus) {
            $lastStatus->effective_till = \Carbon\Carbon::parse($request['effective_from'])->subDay()->format('Y-m-d');

            try {
                $lastStatus->save();
            } catch (\Exception $e) {
                return response()->json(['errorMsg' => $e->getMessage()], 500);
            }
        }
        $employee = Employee::find($request['employee_id']);

        $user = User::find($employee->user_id);
        DB::beginTransaction();

        $old_user_data = $user->toArray();

        if ($request['status'] === 'Retired') {
            $user->is_retired = 1;
            $user->edited_by = auth()->id();
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
            $user->save();
            $user->history()->create($old_user_data);

            $employeeStatus->save();

            DB::commit();
            return response()->json([
                'successMsg' => 'Employee Status Created!',
                'data' => $employeeStatus
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
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

        // $isSmallDate = EmployeeStatus::where('employee_id', $request['employee_id'])->where('effective_from', '>', $request['effective_from'])->get()->first();
        // if ($isSmallDate) return response()->json(['errorMsg' => 'Effective From date is smaller than previous!'], 400);

        $employee = Employee::find($employeeStatus->employee_id);

        $user = User::find($employee->user_id);

        DB::beginTransaction();

        $old_user_data = $user->toArray();
        $old_data = $employeeStatus->toArray();

        if ($request['status'] === 'Retired') {
            $user->is_retired = 1;
            $user->edited_by = auth()->id();
        }

        $employeeStatus->status = $request['status'];
        $employeeStatus->effective_from = $request['effective_from'];
        $employeeStatus->effective_till = $request['effective_till'];
        $employeeStatus->remarks = $request['remarks'];
        $employeeStatus->order_reference = $request['order_reference'];
        $employeeStatus->edited_by = auth()->id();

        try {
            $user->save();
            $user->history()->create($old_user_data);

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

        $query->when('employee_id', fn($q) => $q->where('employee_id', request('employee_id')));

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
        $data = EmployeeStatus::with('history.addedBy.roles:id,name', 'history.editedBy.roles:id,name', 'addedBy.roles:id,name', 'editedBy.roles:id,name', 'employee')->find($id);
        return response()->json(['data' => $data]);
    }
}
