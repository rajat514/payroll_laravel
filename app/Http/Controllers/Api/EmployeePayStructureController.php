<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeePayStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeePayStructureController extends Controller
{
    private \App\Models\User $user;

    private $all_permission_roles = ['IT Admin', 'Director', 'Pension Operator'];
    private $can_add_roles = ['IT Admin', 'Director', 'Pension Operator'];
    private $can_update_roles = ['IT Admin', 'Director', 'Pension Operator'];
    private $can_view_roles = ['IT Admin', 'Director', 'Pension Operator', ' Administrative Officer'];

    function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = \App\Models\User::find(auth()->id());
            return $next($request);
        });
    }

    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = EmployeePayStructure::with('employee', 'payMatrixCell.payMatrixLevel');

        $query->when(
            $this->user->institute !== 'BOTH',
            fn($q) => $q->whereHas(
                'employee',
                fn($qn) => $qn->where('institute', $this->user->institute)
            )
        );

        $query->when(
            request('employee_id'),
            fn($q) => $q->where('employee_id', request('employee_id'))
        );

        $query->when(
            request('increment_month'),
            fn($q) => $q->whereHas(
                'employee',
                fn($qe) => $qe->where('increment_month', 'LIKE', '%' . request('increment_month') . '%')
            )
        );

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function show($id)
    {
        $data = EmployeePayStructure::with(
            'history.addedBy.roles:id,name',
            'history.editedBy.roles:id,name',
            'addedBy.roles:id,name',
            'editedBy.roles:id,name',
            'history.employee',
            'history.PayMatrixCell.payMatrixLevel',
            'employee',
            'payMatrixCell.payMatrixLevel'
        )->find($id);

        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|numeric|exists:employees,id',
            'matrix_cell_id' => 'required|numeric|exists:pay_matrix_cells,id',
            // 'commission' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'order_reference' => 'nullable|max:50'
        ]);

        $hasPayStructure = EmployeePayStructure::where('employee_id', $request['employee_id'])->get()->first();
        if ($hasPayStructure) {
            return response()->json(['errorMsg' => 'This employee pay structure already added!'], 400);
        }

        $employee = Employee::find($request['employee_id']);
        if (!$employee) {
            return response()->json(['errorMsg' => 'Employee not found!'], 404);
        }
        if ($employee->date_of_joining > $request['effective_from']) {
            return response()->json(['errorMsg' => 'Effective from date is smaller than the date of joining of employee!',], 400);
        }

        $payStructure = new EmployeePayStructure();
        $payStructure->employee_id = $request['employee_id'];
        $payStructure->matrix_cell_id = $request['matrix_cell_id'];
        $payStructure->commission = 0;
        $payStructure->effective_from = $request['effective_from'];
        $payStructure->effective_till = $request['effective_till'];
        $payStructure->order_reference = $request['order_reference'];
        $payStructure->added_by = auth()->id();

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
            // 'commission' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'order_reference' => 'nullable|max:50'
        ]);

        DB::beginTransaction();

        $old_data = $payStructure->toArray();

        $payStructure->employee_id = $request['employee_id'];
        $payStructure->matrix_cell_id = $request['matrix_cell_id'];
        $payStructure->commission = 0;
        $payStructure->effective_from = $request['effective_from'];
        $payStructure->effective_till = $request['effective_till'];
        $payStructure->order_reference = $request['order_reference'];
        $payStructure->edited_by = auth()->id();

        try {
            $payStructure->save();

            $payStructure->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Employee Pay Structure updated!', 'data' => $payStructure]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
