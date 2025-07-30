<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GPFContribution;
use App\Models\GPFContributionClone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GPFContributionController extends Controller
{
    /**
     * Display a listing of GPF contributions.
     */
    public function index(): JsonResponse
    {
        try {
            $gpfContributions = GPFContribution::with(['addedBy', 'editedBy'])
                ->orderBy('effective_from', 'desc')
                ->get();

            return response()->json([
                'data' => $gpfContributions
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created GPF contribution.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'rate_percentage' => 'required|numeric|min:0|max:100',
                'effective_from' => 'required|date',
                'effective_till' => 'nullable|date|after:effective_from',
            ]);

            $isSmallDate = GPFContribution::where('effective_from', '>=', $request['effective_from'])->get()->first();
            if ($isSmallDate) return response()->json(['errorMsg' => 'Effective From date is smaller than previous!'], 400);

            $previousData = GPFContribution::get()->last();

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

            $gpfContribution = GPFContribution::create([
                'rate_percentage' => $request->rate_percentage,
                'effective_from' => $request->effective_from,
                'effective_till' => $request->effective_till,
                'added_by' => auth()->id(),
            ]);

            DB::commit();
            return response()->json(['successMsg' => 'GPF contribution created successfully', 'data' => $gpfContribution], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified GPF contribution.
     */
    public function show($id): JsonResponse
    {
        try {
            $gpfContribution = GPFContribution::with('history.addedBy.roles:id,name', 'history.editedBy.roles:id,name', 'addedBy.roles:id,name', 'editedBy.roles:id,name')
                ->find($id);

            if (!$gpfContribution) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'GPF contribution not found'
                ], 404);
            }

            return response()->json([
                'successMsg' => 'GPF contribution retrieved successfully',
                'data' => $gpfContribution
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified GPF contribution.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $gpfContribution = GPFContribution::find($id);

        if (!$gpfContribution) {
            return response()->json(['errorMsg' => 'GPF contribution not found'], 404);
        }

        $request->validate([
            'rate_percentage' => 'required|numeric|min:0|max:100',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
        ]);

        DB::beginTransaction();

        $old_data = $gpfContribution->toArray();

        $gpfContribution->rate_percentage = $request->rate_percentage;
        $gpfContribution->effective_from = $request->effective_from;
        $gpfContribution->effective_till = $request->effective_till;
        $gpfContribution->edited_by = auth()->id();

        try {
            $gpfContribution->save();

            $gpfContribution->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'GPF contribution updated successfully', 'data' => $gpfContribution], 200);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
