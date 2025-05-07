<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeGIS;
use Illuminate\Http\Request;

class EmployeeGISController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = EmployeeGIS::with('employee');

        $query->when(
            request('employee_id'),
            fn($q) => $q->where('employee_id', 'LIKE', '%' . request('employee_id') . '%')
        );

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|numeric|exists:employees,id',
            'scheme_category' => 'required|string',
            'monthly_subscription' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date',
        ]);

        $employeeGIS = new EmployeeGIS();
        $employeeGIS->employee_id = $request['employee_id'];
        $employeeGIS->scheme_category = $request['scheme_category'];
        $employeeGIS->monthly_subscription = $request['monthly_subscription'];
        $employeeGIS->effective_from = $request['effective_from'];
        $employeeGIS->effective_till = $request['effective_till'];

        try {
            $employeeGIS->save();

            return response()->json(['successMsg' => 'Employee GIS Created!', 'data' => $employeeGIS]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $employeeGIS = EmployeeGIS::find($id);
        if (!$employeeGIS) return response()->json(['errorMsg' => 'Employee GIS not found!'], 404);

        $request->validate([
            'employee_id' => 'required|numeric|exists:employees,id',
            'scheme_category' => 'required|string',
            'monthly_subscription' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date',
        ]);

        $employeeGIS->employee_id = $request['employee_id'];
        $employeeGIS->scheme_category = $request['scheme_category'];
        $employeeGIS->monthly_subscription = $request['monthly_subscription'];
        $employeeGIS->effective_from = $request['effective_from'];
        $employeeGIS->effective_till = $request['effective_till'];

        try {
            $employeeGIS->save();

            return response()->json(['successMsg' => 'Employee GIS Created!', 'data' => $employeeGIS]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
