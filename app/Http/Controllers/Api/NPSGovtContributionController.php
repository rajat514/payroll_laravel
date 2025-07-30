<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NPSGovtContribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NPSGovtContributionController extends Controller
{
    // List all records with pagination
    public function index(Request $request)
    {
        $query = NPSGovtContribution::query();

        $query->when(
            request('type'),
            fn($q) => $q->where('type', request('type'))
        );

        $data = $query->orderBy('effective_from', 'desc')->get();

        return response()->json(['data' => $data,]);
    }

    // Store a new record
    public function store(Request $request)
    {
        $request->validate([
            'rate_percentage' => 'required|integer',
            'type' => 'required|in:Employee,GOVT',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
        ]);


        $isSmallDate = NPSGovtContribution::where('type', $request['type'])->where('effective_from', '>=', $request['effective_from'])->get()->first();
        if ($isSmallDate) return response()->json(['errorMsg' => 'Effective From date is smaller than previous!'], 400);

        $previousData = NPSGovtContribution::where('type', $request['type'])->get()->last();

        DB::beginTransaction();

        if ($previousData) {
            $previousData->effective_from = \Carbon\Carbon::parse($request['effective_from'])->subDay()->format('Y-m-d');

            try {
                $previousData->save();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['errorMsg' => $e->getMessage()], 500);
            }
        }


        $nps = new NPSGovtContribution();
        $nps->rate_percentage = $request->rate_percentage;
        $nps->type = $request->type;
        $nps->effective_from = $request->effective_from;
        $nps->effective_till = $request->effective_till;
        $nps->added_by = Auth::id();

        try {
            $nps->save();
            DB::commit();
            return response()->json(['successMsg' => 'NPS Govt Contribution created!', 'data' => $nps]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    // Update an existing record
    public function update(Request $request, $id)
    {
        $nps = NPSGovtContribution::find($id);
        if (!$nps) {
            return response()->json(['errorMsg' => 'Record not found!'], 404);
        }

        $validated = $request->validate([
            'rate_percentage' => 'required|integer',
            'type' => 'required|in:Employee,GOVT',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
        ]);


        DB::beginTransaction();

        $old_data = $nps->toArray();

        $nps->rate_percentage = $request->rate_percentage;
        $nps->type = $request->type;
        $nps->effective_from = $request->effective_from;
        $nps->effective_till = $request->effective_till;
        $nps->edited_by = Auth::id();

        try {
            $nps->save();
            $nps->history()->create($old_data);
            DB::commit();
            return response()->json(['successMsg' => 'NPS Govt Contribution updated!', 'data' => $nps]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    // Show a record with its history
    public function show($id)
    {
        $data = \App\Models\NPSGovtContribution::with('history.addedBy.roles:id,name', 'history.editedBy.roles:id,name', 'addedBy.roles:id,name', 'editedBy.roles:id,name')->find($id);
        return response()->json(['data' => $data]);
    }
}
