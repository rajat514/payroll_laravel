<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NonPracticingAllowanceRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NonPracticingAllowanceRateController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = NonPracticingAllowanceRate::query();

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function show($id)
    {
        $data = NonPracticingAllowanceRate::with('addedBy', 'editedBy', 'history.addedBy', 'history.editedBy')->find($id);

        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        $request->validate([
            'applicable_post' => 'required|string|max:55',
            'rate_percentage' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'notification_ref' => 'nullable|string'
        ]);

        $isSmallDate = NonPracticingAllowanceRate::where('effective_from', '>=', $request['effective_from'])->get()->first();
        if ($isSmallDate) return response()->json(['errorMsg' => 'Effective From date is smaller than previous!'], 400);

        $nPAllowance = new NonPracticingAllowanceRate();
        $nPAllowance->applicable_post = $request['applicable_post'];
        $nPAllowance->rate_percentage = $request['rate_percentage'];
        $nPAllowance->effective_from = $request['effective_from'];
        $nPAllowance->effective_till = $request['effective_till'];
        $nPAllowance->notification_ref = $request['notification_ref'];
        $nPAllowance->added_by = auth()->id();

        try {
            $nPAllowance->save();

            return response()->json(['successMsg' => 'Non Practicing Allowance Rate Created!', 'data' => $nPAllowance]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $nPAllowance = NonPracticingAllowanceRate::find($id);
        if (!$nPAllowance) return response()->json(['errorMsg' => 'Non Practicing Allowance Rate not found!'], 404);

        $request->validate([
            'applicable_post' => 'required|string|max:55',
            'rate_percentage' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'notification_ref' => 'nullable|string'
        ]);

        $isSmallDate = NonPracticingAllowanceRate::where('effective_from', '>=', $request['effective_from'])->get()->first();
        if ($isSmallDate) return response()->json(['errorMsg' => 'Effective From date is smaller than previous!'], 400);

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
