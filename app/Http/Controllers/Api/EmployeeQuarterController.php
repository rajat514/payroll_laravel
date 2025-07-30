<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\EmployeeQuarter;
use Illuminate\Support\Facades\DB;

class EmployeeQuarterController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = EmployeeQuarter::with('employee');
        $query->when(
            request('employee_id'),
            fn($q) => $q->where('employee_id', request('employee_id'))
        );

        $query->when(
            request('quarter_id'),
            fn($q) => $q->where('quarter_id', request('quarter_id'))
        );

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        $request->validate(
            [
                'employee_id' => 'required|numeric|exists:employees,id',
                'quarter_id' => 'required|numeric|exists:quarters,id',
                'date_of_allotment' => 'required|date',
                'date_of_occupation' => 'required|date|after:date_of_allotment',
                'date_of_leaving' => 'nullable|date|after:date_of_occupation|after:date_of_allotment',
                'is_current' => 'boolean|in:1,0',
                'order_reference' => 'nullable|string|max:191'
            ]
        );

        $employee = Employee::find($request['employee_id']);
        if (!$employee) return response()->json(['errorMsg' => 'Employee Not Found!']);

        if ($employee->date_of_joining > $request['date_of_allotment']) {
            return response()->json(['errorMsg' => 'Date of allotement date is smaller than the date of joining of employee!',], 404);
        }

        $checkQuarter = EmployeeQuarter::where('quarter_id', $request['quarter_id'])->value('id');
        if ($checkQuarter) return response()->json(['errorMsg' => 'This Quarter has already alloted!']);

        $isSmallAllotmentDate = EmployeeQuarter::where('employee_id', $request['employee_id'])->where('date_of_allotment', '>=', $request['date_of_allotment'])->get()->first();
        if ($isSmallAllotmentDate) return response()->json(['errorMsg' => 'Date of allotment is smaller than previous!'], 400);

        $isSmallOccupationDate = EmployeeQuarter::where('employee_id', $request['employee_id'])->where('date_of_occupation', '>=', $request['date_of_occupation'])->get()->first();
        if ($isSmallOccupationDate) return response()->json(['errorMsg' => 'Date of occupation is smaller than previous!'], 400);

        $employeeQuarter = new EmployeeQuarter();
        $employeeQuarter->employee_id = $request['employee_id'];
        $employeeQuarter->quarter_id = $request['quarter_id'];
        $employeeQuarter->date_of_allotment = $request['date_of_allotment'];
        $employeeQuarter->date_of_occupation = $request['date_of_occupation'];
        $employeeQuarter->date_of_leaving = $request['date_of_leaving'];
        $employeeQuarter->is_current = $request['is_current'];
        $employeeQuarter->order_reference = $request['order_reference'];
        $employeeQuarter->added_by = auth()->id();

        $employee->hra_eligibility = 0;

        $employeeQuarter->is_occupied = \Carbon\Carbon::parse($request['date_of_occupation'])->lte(\Carbon\Carbon::today()) ? 1 : 0;


        try {
            $employee->save();
            $employeeQuarter->save();

            return response()->json(['successMsg' => 'Employee Quarter Created!', 'data' => $employeeQuarter]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $employeeQuarter = EmployeeQuarter::find($id);
        if (!$employeeQuarter) return response()->json(['errorMsg' => 'Employee Quarter Not Found!']);

        $request->validate(
            [
                'employee_id' => 'required|numeric|exists:employees,id',
                'quarter_id' => 'required|numeric|exists:quarters,id',
                'date_of_allotment' => 'required|date',
                'date_of_occupation' => 'required|date|after:date_of_allotment',
                'date_of_leaving' => 'nullable|date|after:date_of_occupation|after:date_of_allotment',
                'is_current' => 'boolean|in:1,0',
                'order_reference' => 'nullable|string|max:191',
                'is_occupied' => 'boolean|in:1,0'
            ]
        );

        $employee = Employee::find($request['employee_id']);
        if (!$employee) return response()->json(['errorMsg' => 'Employee Not Found!']);

        DB::beginTransaction();

        $old_data = $employeeQuarter->toArray();

        $employeeQuarter->employee_id = $request['employee_id'];
        $employeeQuarter->quarter_id = $request['quarter_id'];
        $employeeQuarter->date_of_allotment = $request['date_of_allotment'];
        $employeeQuarter->date_of_occupation = $request['date_of_occupation'];
        $employeeQuarter->date_of_leaving = $request['date_of_leaving'];
        $employeeQuarter->is_current = $request['is_current'];
        $employeeQuarter->order_reference = $request['order_reference'];
        $employeeQuarter->is_occupied = $request['is_occupied'];
        $employeeQuarter->edited_by = auth()->id();

        try {
            $employeeQuarter->save();

            $employeeQuarter->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Employee Quarter Updated!', 'data' => $employeeQuarter]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function changeStatus($id)
    {
        $employeeQuarter = EmployeeQuarter::find($id);
        if (!$employeeQuarter) return response()->json(['errorMsg' => 'Employee Quarter not found!']);

        DB::beginTransaction();

        $old_data = $employeeQuarter->toArray();

        $employeeQuarter->is_current = !$employeeQuarter->is_current;
        $employeeQuarter->edited_by = auth()->id();

        try {
            $employeeQuarter->save();

            $employeeQuarter->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Employee Quarter current status updated!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function show($id)
    {
        $data = EmployeeQuarter::with(
            'quarter',
            'history.quarter',
            'history.addedBy.roles:id,name',
            'history.editedBy.roles:id,name',
            'addedBy.roles:id,name',
            'editedBy.roles:id,name'
        )->find($id);

        return response()->json(['data' => $data]);
    }
}
