<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmployeeDesignation;
use Illuminate\Support\Facades\DB;

class EmployeeDesignationController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = EmployeeDesignation::with('employee');
        $query->when(
            request('employee_id'),
            fn($q) => $q->where('employee_id', request('employee_id'))
        );

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();
        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function show($id)
    {
        $data = EmployeeDesignation::with('addedBy', 'editedBy', 'history.editedBy')->find($id);
        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        $request->validate(
            [
                'employee_id' => 'required|numeric|exists:employees,id',
                'designation' => 'required|string|min:2|max:191',
                'cadre' => 'required|string|min:2|max:191',
                'job_group' => 'required|in:A,B,C,D',
                'effective_from' => 'required|date',
                'effective_till' => 'nullable|date|after:effective_from',
                'promotion_order_no' => 'nullable|string|max:50',
            ]
        );

        $designation = EmployeeDesignation::where('employee_id', $request['employee_id'])->get()->last();

        $isSmallDate = EmployeeDesignation::where('employee_id', $request['employee_id'])->where('effective_from', '>=', $request['effective_from'])->get()->first();
        if ($isSmallDate) return response()->json(['errorMsg' => 'Effective From date is smaller than previous!'], 400);

        DB::beginTransaction();

        if ($designation) {
            $designation->effective_till = $request['effective_from'];
            try {
                DB::commit();
                $designation->save();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['errorMsg' => $e->getMessage()], 500);
            }
        }

        $employeeDesignation = new EmployeeDesignation();
        $employeeDesignation->employee_id = $request['employee_id'];
        $employeeDesignation->designation = $request['designation'];
        $employeeDesignation->cadre = $request['cadre'];
        $employeeDesignation->job_group = $request['job_group'];
        $employeeDesignation->effective_from = $request['effective_from'];
        $employeeDesignation->effective_till = $request['effective_till'];
        $employeeDesignation->promotion_order_no = $request['promotion_order_no'];
        $employeeDesignation->added_by = auth()->id();

        try {
            $employeeDesignation->save();
            DB::commit();
            return response()->json(['successMsg' => 'Employee Designation Added!', 'data' => $employeeDesignation]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $employeeDesignation = EmployeeDesignation::find($id);
        if (!$employeeDesignation) return response()->json(['errorMsg' => 'Employee Designation Not Found!']);

        $request->validate(
            [
                'designation' => 'required|string|min:2|max:191',
                'cadre' => 'required|string|min:2|max:191',
                'job_group' => 'required|in:A,B,C,D',
                'effective_from' => 'required|date',
                'effective_till' => 'nullable|date|after:effective_from',
                'promotion_order_no' => 'nullable|string|max:50',
            ]
        );

        $isSmallDate = EmployeeDesignation::where('employee_id', $request['employee_id'])->where('effective_from', '>', $request['effective_from'])->get()->first();
        if ($isSmallDate) return response()->json(['errorMsg' => 'Effective From date is smaller than previous!'], 400);

        DB::beginTransaction();

        $old_data = $employeeDesignation->toArray();

        $employeeDesignation->designation = $request['designation'];
        $employeeDesignation->cadre = $request['cadre'];
        $employeeDesignation->job_group = $request['job_group'];
        $employeeDesignation->effective_from = $request['effective_from'];
        $employeeDesignation->effective_till = $request['effective_till'];
        $employeeDesignation->promotion_order_no = $request['promotion_order_no'];
        $employeeDesignation->edited_by = auth()->id();

        try {
            $employeeDesignation->save();

            $employeeDesignation->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Employee Designation Updated!', 'data' => $employeeDesignation]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
