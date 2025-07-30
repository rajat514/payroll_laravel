<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UniformAllowanceRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UniformAllowanceRateController extends Controller
{
    private \App\Models\User $user;

    private $all_permission_roles = ['IT Admin', 'Director'];

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

        $query = UniformAllowanceRate::query();

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function show($id)
    {
        $data = UniformAllowanceRate::with('history.addedBy.roles:id,name', 'history.editedBy.roles:id,name', 'addedBy.roles:id,name', 'editedBy.roles:id,name')->find($id);

        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        // Check if user has required roles
        if (!$this->user->hasAnyRole($this->all_permission_roles)) {
            return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        }

        $request->validate([
            'applicable_post' => 'required|string',
            'amount' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'notification_ref' => 'nullable|string'
        ]);


        $isSmallDate = UniformAllowanceRate::where('effective_from', '>=', $request['effective_from'])->get()->first();
        if ($isSmallDate) return response()->json(['errorMsg' => 'Effective From date is smaller than previous!'], 400);

        $uaRate = UniformAllowanceRate::get()->last();

        DB::beginTransaction();

        if ($uaRate) {
            $uaRate->effective_till = \Carbon\Carbon::parse($request['effective_from'])->subDay()->format('Y-m-d');

            try {
                $uaRate->save();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['errorMsg' => $e->getMessage()], 500);
            }
        }

        $uniformAllowance = new UniformAllowanceRate();
        $uniformAllowance->applicable_post = $request['applicable_post'];
        $uniformAllowance->amount = $request['amount'];
        $uniformAllowance->effective_from = $request['effective_from'];
        $uniformAllowance->effective_till = $request['effective_till'];
        $uniformAllowance->notification_ref = $request['notification_ref'];
        $uniformAllowance->added_by = auth()->id();

        try {
            $uniformAllowance->save();
            DB::commit();
            return response()->json(['successMsg' => 'Uniform Allowance Rate Created!', 'data' => $uniformAllowance]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        // Check if user has required roles
        if (!$this->user->hasAnyRole($this->all_permission_roles)) {
            return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        }

        $uniformAllowance = UniformAllowanceRate::find($id);
        if (!$uniformAllowance) return response()->json(['errorMsg' => 'Uniform Allowance Rate not found!'], 404);

        $request->validate([
            'applicable_post' => 'required|string',
            'amount' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'notification_ref' => 'nullable|string'
        ]);


        DB::beginTransaction();

        $old_data = $uniformAllowance->toArray();

        $uniformAllowance->applicable_post = $request['applicable_post'];
        $uniformAllowance->amount = $request['amount'];
        $uniformAllowance->effective_from = $request['effective_from'];
        $uniformAllowance->effective_till = $request['effective_till'];
        $uniformAllowance->notification_ref = $request['notification_ref'];
        $uniformAllowance->edited_by = auth()->id();

        try {
            $uniformAllowance->save();

            $uniformAllowance->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Uniform Allowance Rate Created!', 'data' => $uniformAllowance]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
