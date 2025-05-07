<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmployeeQuarter;

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
                'date_of_occupation' => 'required|date',
                'date_of_leaving' => 'nullable|date',
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
                'date_of_occupation' => 'required|date',
                'date_of_leaving' => 'nullable|date',
                'is_current' => 'boolean|in:1,0',
                'order_reference' => 'nullable|string|max:191'
            ]
        );

        $employeeQuarter->employee_id = $request['employee_id'];
        $employeeQuarter->quarter_id = $request['quarter_id'];
        $employeeQuarter->date_of_allotment = $request['date_of_allotment'];
        $employeeQuarter->date_of_occupation = $request['date_of_occupation'];
        $employeeQuarter->date_of_leaving = $request['date_of_leaving'];
        $employeeQuarter->is_current = $request['is_current'];
        $employeeQuarter->order_reference = $request['order_reference'];

        try {
            $employeeQuarter->save();

            return response()->json(['successMsg' => 'Employee Quarter Updated!', 'data' => $employeeQuarter]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function show($id)
    {
        $employeeQuarter = EmployeeQuarter::find($id);
        if (!$employeeQuarter) return response()->json(['errorMsg' => 'Employee Quarter not found!']);

        $employeeQuarter->is_current = !$employeeQuarter->is_current;

        try {
            $employeeQuarter->save();

            return response()->json(['successMsg' => 'Employee Quarter current status updated!']);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
