<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        $data = $query->offset($offset)->limit($limit)->get();

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

        $checkQuarter = EmployeeQuarter::where('quarter_id', $request['quarter_id'])->value('id');
        if ($checkQuarter) return response()->json(['errorMsg' => 'This Quarter has already alloted!']);

        $employeeQuarter = new EmployeeQuarter();
        $employeeQuarter->employee_id = $request['employee_id'];
        $employeeQuarter->quarter_id = $request['quarter_id'];
        $employeeQuarter->date_of_allotment = $request['date_of_allotment'];
        $employeeQuarter->date_of_occupation = $request['date_of_occupation'];
        $employeeQuarter->date_of_leaving = $request['date_of_leaving'];
        $employeeQuarter->is_current = $request['is_current'];
        $employeeQuarter->order_reference = $request['order_reference'];
        $employeeQuarter->added_by = auth()->id();

        try {
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
        $data = EmployeeQuarter::with('addedBy', 'editedBy', 'history.addedBy', 'history.editedBy')->find($id);

        return response()->json(['data' => $data]);
    }
}
