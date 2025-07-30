<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayMatrixCell;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayMatrixCellController extends Controller
{
    private \App\Models\User $user;

    private $all_permission_roles = ['IT Admin', 'Director', 'Salary Processing Coordinator (NIOH)', 'Salary Processing Coordinator (ROHC)'];

    function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = \App\Models\User::find(auth()->id());
            return $next($request);
        });
    }

    function index()
    {
        // $page = request('page') ? (int)request('page') : 1;
        // $limit = request('limit') ? (int)request('limit') : 30;
        // $offset = ($page - 1) * $limit;

        $query = PayMatrixCell::with('payMatrixLevel.PayCommission');

        $query->when(
            request('matrix_level_id'),
            fn($q) => $q->where('matrix_level_id', request('matrix_level_id'))
        );
        $total_count = $query->count();

        $data = $query->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function show($id)
    {
        $data = PayMatrixCell::with('payMatrixLevel', 'history.addedBy.roles:id,name', 'history.editedBy.roles:id,name', 'addedBy.roles:id,name', 'editedBy.roles:id,name', 'history.payMatrixLevel')->find($id);

        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        // Check if user has required roles
        if (!$this->user->hasAnyRole($this->all_permission_roles)) {
            return response()->json(['errorMsg' => 'Access Denied! Only IT Admin and Director can perform this action.'], 403);
        }

        $request->validate([
            'matrix_level_id' => 'required|numeric|exists:pay_matrix_levels,id',
            'index' => ['required', 'numeric', 'regex:/^\d{1,2}$/'],
            'amount' => 'required|numeric'
        ]);

        $payMatrixCell = new PayMatrixCell();
        $payMatrixCell->matrix_level_id = $request['matrix_level_id'];
        $payMatrixCell->index = $request['index'];
        $payMatrixCell->amount = $request['amount'];
        $payMatrixCell->added_by = auth()->id();

        try {
            $payMatrixCell->save();

            return response()->json(['successMsg' => 'Pay Matrix Cell Created!', 'data' => $payMatrixCell]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        // Check if user has required roles
        if (!$this->user->hasAnyRole($this->all_permission_roles)) {
            return response()->json(['errorMsg' => 'Access Denied! Only IT Admin and Director can perform this action.'], 403);
        }

        $payMatrixCell = PayMatrixCell::find($id);
        if (!$payMatrixCell) return response()->json(['errorMsg' => 'Pay Matrix Cell not found!']);

        $request->validate([
            'matrix_level_id' => 'required|numeric|exists:pay_matrix_levels,id',
            'index' => ['required', 'numeric', 'regex:/^\d{1,2}$/'],
            'amount' => 'required|numeric'
        ]);

        DB::beginTransaction();

        $old_data = $payMatrixCell->toArray();

        $payMatrixCell->matrix_level_id = $request['matrix_level_id'];
        $payMatrixCell->index = $request['index'];
        $payMatrixCell->amount = $request['amount'];
        $payMatrixCell->edited_by = auth()->id();
        try {
            $payMatrixCell->save();

            $payMatrixCell->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Pay Matrix Cell Updated!', 'data' => $payMatrixCell]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
