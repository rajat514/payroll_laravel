<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeePayStructure;
use Illuminate\Http\Request;

class EmployeePayStructureController extends Controller
{
    function index()
    {
        $data = EmployeePayStructure::with('employee', 'payMatrixCell')->get();

        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|numeric|exists:employees,id',
            'matrix_cell_id' => 'required|numeric|exists:pay_matrix_cells,id',
            'commission' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date',
            'order_reference' => 'nullable|max:50'
        ]);

        $payStructure = new EmployeePayStructure();
        $payStructure->employee_id = $request['employee_id'];
        $payStructure->matrix_cell_id = $request['matrix_cell_id'];
        $payStructure->commission = $request['commission'];
        $payStructure->effective_from = $request['effective_from'];
        $payStructure->effective_till = $request['effective_till'];
        $payStructure->order_reference = $request['order_reference'];

        try {
            $payStructure->save();
            return response()->json(['successMsg' => 'Employee Pay Structure created!', 'data' => $payStructure]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $payStructure = EmployeePayStructure::find($id);
        if (!$payStructure) return response()->json(['errorMsg' => 'Employee Pay Structure not found!']);

        $request->validate([
            'employee_id' => 'required|numeric|exists:employees,id',
            'matrix_cell_id' => 'required|numeric|exists:pay_matrix_cells,id',
            'commission' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date',
            'order_reference' => 'nullable|max:50'
        ]);

        $payStructure->employee_id = $request['employee_id'];
        $payStructure->matrix_cell_id = $request['matrix_cell_id'];
        $payStructure->commission = $request['commission'];
        $payStructure->effective_from = $request['effective_from'];
        $payStructure->effective_till = $request['effective_till'];
        $payStructure->order_reference = $request['order_reference'];

        try {
            $payStructure->save();
            return response()->json(['successMsg' => 'Employee Pay Structure updated!', 'data' => $payStructure]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
