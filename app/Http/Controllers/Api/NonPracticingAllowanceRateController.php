<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NonPracticingAllowanceRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NonPracticingAllowanceRateController extends Controller
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

        $query = NonPracticingAllowanceRate::query();

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function show($id)
    {
        $data = NonPracticingAllowanceRate::with('history.addedBy.roles:id,name', 'history.editedBy.roles:id,name', 'addedBy.roles:id,name', 'editedBy.roles:id,name')->find($id);

        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        // Check if user has required roles
        if (!$this->user->hasAnyRole($this->all_permission_roles)) {
            return response()->json(['errorMsg' => 'Access Denied! Only IT Admin and Director can perform this action.'], 403);
        }

        $request->validate([
            'applicable_post' => 'required|string|max:55',
            'rate_percentage' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'notification_ref' => 'nullable|string'
        ]);

        $isSmallDate = NonPracticingAllowanceRate::where('effective_from', '>=', $request['effective_from'])->get()->first();
        if ($isSmallDate) return response()->json(['errorMsg' => 'Effective From date is smaller than previous!'], 400);

        $npa = NonPracticingAllowanceRate::get()->last();

        DB::beginTransaction();

        if ($npa) {
            $npa->effective_till = \Carbon\Carbon::parse($request['effective_from'])->subDay()->format('Y-m-d');

            try {
                $npa->save();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['errorMsg' => $e->getMessage()], 500);
            }
        }

        $nPAllowance = new NonPracticingAllowanceRate();
        $nPAllowance->applicable_post = $request['applicable_post'];
        $nPAllowance->rate_percentage = $request['rate_percentage'];
        $nPAllowance->effective_from = $request['effective_from'];
        $nPAllowance->effective_till = $request['effective_till'];
        $nPAllowance->notification_ref = $request['notification_ref'];
        $nPAllowance->added_by = auth()->id();

        try {
            $nPAllowance->save();
            DB::commit();
            return response()->json(['successMsg' => 'Non Practicing Allowance Rate Created!', 'data' => $nPAllowance]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        // Check if user has required roles
        if (!$this->user->hasAnyRole($this->all_permission_roles)) {
            return response()->json(['errorMsg' => 'Access Denied! Only IT Admin and Director can perform this action.'], 403);
        }

        $nPAllowance = NonPracticingAllowanceRate::find($id);
        if (!$nPAllowance) return response()->json(['errorMsg' => 'Non Practicing Allowance Rate not found!'], 404);

        $request->validate([
            'applicable_post' => 'required|string|max:55',
            'rate_percentage' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'notification_ref' => 'nullable|string'
        ]);

        DB::beginTransaction();

        $old_data = $nPAllowance->toArray();

        $nPAllowance->applicable_post = $request['applicable_post'];
        $nPAllowance->rate_percentage = $request['rate_percentage'];
        $nPAllowance->effective_from = $request['effective_from'];
        $nPAllowance->effective_till = $request['effective_till'];
        $nPAllowance->notification_ref = $request['notification_ref'];
        $nPAllowance->edited_by = auth()->id();

        try {
            $nPAllowance->save();

            $nPAllowance->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Non Practicing Allowance Rate Updated!', 'data' => $nPAllowance]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
