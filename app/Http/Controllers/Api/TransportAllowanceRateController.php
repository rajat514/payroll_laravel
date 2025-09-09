<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeTransportAllowance;
use App\Models\TransportAllowanceRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransportAllowanceRateController extends Controller
{

    private \App\Models\User $user;

    private $can_view_roles = ['IT Admin', 'Director', 'Administrative Officer', 'Account Officer', 'Coordinator - NIOH', 'Coordinator - ROHC', 'Pension Operator', 'End Users'];
    private $can_post_roles = ['IT Admin', 'Director', 'Administrative Officer', 'Account Officer', 'Coordinator - NIOH', 'Coordinator - ROHC', 'Pension Operator'];
    private $can_view_own_roles = ['Coordinator - NIOH', 'Coordinator - ROHC', 'Pension Operator'];
    private $all_permission_roles = ['IT Admin', 'Director', 'Salary Processing Coordinator (ROHC)', 'Salary Processing Coordinator (NIOH)'];

    public function __construct()
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

        $query = EmployeeTransportAllowance::query();

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {

        if (!$this->user->hasAnyRole($this->all_permission_roles)) {
            return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        }

        $request->validate([
            'pay_matrix_level' => 'required|string|unique:employee_transport_allowances,pay_matrix_level',
            'amount' => 'required|numeric',
        ]);

        $transportAllowance = new EmployeeTransportAllowance();
        $transportAllowance->pay_matrix_level = $request['pay_matrix_level'];
        $transportAllowance->amount = $request['amount'];
        $transportAllowance->added_by = auth()->id();

        try {
            $transportAllowance->save();

            return response()->json(['successMsg' => 'Transport Allowance Rate Created!', 'data' => $transportAllowance]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        if (!$this->user->hasAnyRole($this->all_permission_roles)) {
            return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        }

        $transportAllowance = EmployeeTransportAllowance::find($id);
        if (!$transportAllowance) return response()->json(['errorMsg' => 'Transport Allowance Rate not found!'], 404);

        $request->validate([
            'pay_matrix_level' => "required|string|unique:employee_transport_allowances,pay_matrix_level,$id,id",
            'amount' => 'required|numeric',
        ]);

        DB::beginTransaction();

        $old_data = $transportAllowance->toArray();

        $transportAllowance->pay_matrix_level = $request['pay_matrix_level'];
        $transportAllowance->amount = $request['amount'];
        $transportAllowance->added_by = auth()->id();
        $transportAllowance->edited_by = auth()->id();

        try {
            $transportAllowance->save();

            $transportAllowance->history()->create($old_data);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
        return response()->json(['successMsg' => 'Transport Allowance Rate Updated!', 'data' => $transportAllowance]);
    }

    function show($id)
    {
        $data = EmployeeTransportAllowance::with('history.addedBy.roles:id,name', 'history.editedBy.roles:id,name', 'addedBy.roles:id,name', 'editedBy.roles:id,name')->find($id);

        return response()->json(['data' => $data]);
    }
}
