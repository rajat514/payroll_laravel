<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeGIS;
use App\Models\GISEligibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeGISController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = GISEligibility::query();

        // $query->when(
        //     request('employee_id'),
        //     fn($q) => $q->where('employee_id', 'LIKE', '%' . request('employee_id') . '%')
        // );

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        $request->validate([
            'pay_matrix_level' => 'required|string|unique:g_i_s_eligibilities,pay_matrix_level',
            'scheme_category' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        $employeeGIS = new GISEligibility();
        $employeeGIS->pay_matrix_level = $request['pay_matrix_level'];
        $employeeGIS->scheme_category = $request['scheme_category'];
        $employeeGIS->amount = $request['amount'];
        $employeeGIS->added_by = auth()->id();

        try {
            $employeeGIS->save();

            return response()->json(['successMsg' => 'Employee GIS Created!', 'data' => $employeeGIS]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $employeeGIS = GISEligibility::find($id);
        if (!$employeeGIS) return response()->json(['errorMsg' => 'Employee GIS not found!'], 404);

        $request->validate([
            'pay_matrix_level' => "required|string|unique:g_i_s_eligibilities,pay_matrix_level,$id,id",
            'scheme_category' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        DB::beginTransaction();

        $old_data = $employeeGIS->toArray();

        $employeeGIS->pay_matrix_level = $request['pay_matrix_level'];
        $employeeGIS->scheme_category = $request['scheme_category'];
        $employeeGIS->amount = $request['amount'];
        $employeeGIS->edited_by = auth()->id();

        try {
            $employeeGIS->save();

            $employeeGIS->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Employee GIS Updated!', 'data' => $employeeGIS]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function show($id)
    {
        $data = GISEligibility::with('addedBy', 'editedBy', 'history.addedBy', 'history.editedBy')->find($id);

        return response()->json(['data' => $data]);
    }
}
