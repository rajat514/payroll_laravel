<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonthlyPension;
use Illuminate\Http\Request;

class MonthlyPensionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = MonthlyPension::with('pensioner', 'dr', 'addedBy.role', 'editedBy.role');

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'pensioner_id' => 'required|exists:pensioner_information,id',
            'month' => 'required|date',
            'basic_pension' => 'required|numeric',
            'commutation_amount' => 'required|numeric',
            'additional_pension' => 'required|numeric',
            'dr_id' => 'nullable|exists:dearness_reliefs,id',
            'dr_amount' => 'nullable|numeric',
            'medical_allowance' => 'required|numeric',
            'remarks' => 'nullable|string|max:255',
            'status' => 'required|in:Pending,Processed,Paid',
        ], [
            'pensioner_id.required' => 'Please select a pensioner.',
            'pensioner_id.exists' => 'The selected pensioner does not exist.',

            'month.required' => 'Please provide the month.',
            'month.date' => 'The month must be a valid date.',

            'basic_pension.required' => 'Basic pension is required.',
            'basic_pension.numeric' => 'Basic pension must be a number.',

            'commutation_amount.required' => 'Commutation amount is required.',
            'commutation_amount.numeric' => 'Commutation amount must be numeric.',

            'additional_pension.required' => 'Additional pension is required.',
            'additional_pension.numeric' => 'Additional pension must be numeric.',

            'dr_id.exists' => 'The selected dearness relief is invalid.',

            'dr_amount.numeric' => 'Dearness relief amount must be numeric.',

            'medical_allowance.required' => 'Medical allowance is required.',
            'medical_allowance.numeric' => 'Medical allowance must be numeric.',

            'remarks.string' => 'Remarks must be a string.',
            'remarks.max' => 'Remarks may not be greater than 255 characters.',

            'status.required' => 'Status is required.',
            'status.in' => 'Status must be one of: Pending, Processed, or Paid.',
        ]);

        $formattedMonth = \Carbon\Carbon::parse($request->month)->startOfMonth()->format('Y-m-d');

        // Calculate totals
        $drAmount = $request->dr_amount ?? 0;
        $totalPension = $request->basic_pension + $request->additional_pension + $drAmount + $request->medical_allowance;
        $totalRecovery = $request->commutation_amount;
        $netPension = $totalPension - $totalRecovery;

        $data = new MonthlyPension();
        $data->pensioner_id = $request['pensioner_id'];
        $data->month = $formattedMonth;
        $data->basic_pension = $request['basic_pension'];
        $data->commutation_amount = $request['commutation_amount'];
        $data->additional_pension = $request['additional_pension'];
        $data->dr_id = $request['dr_id'];
        $data->dr_amount = $drAmount;
        $data->medical_allowance = $request['medical_allowance'];
        $data->total_pension = $totalPension;
        $data->total_recovery = $totalRecovery;
        $data->net_pension = $netPension;
        $data->remarks = $request['remarks'];
        $data->status = $request['status'];
        $data->added_by = auth()->id();

        try {
            $data->save();
            return response()->json([
                'successMsg' => 'Monthly Pension create successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'errorMsg' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = MonthlyPension::find($id);

        if (!$data) return response()->json(['errorMsg' => 'Monthly pension not found!'], 404);

        $request->validate([
            'pensioner_id' => 'required|exists:pensioner_information,id',
            'month' => 'required|date',
            'basic_pension' => 'required|numeric',
            'commutation_amount' => 'required|numeric',
            'additional_pension' => 'required|numeric',
            'dr_id' => 'nullable|exists:dearness_reliefs,id',
            'dr_amount' => 'nullable|numeric',
            'medical_allowance' => 'required|numeric',
            'remarks' => 'nullable|string|max:255',
            'status' => 'required|in:Pending,Processed,Paid',
        ], [
            'pensioner_id.required' => 'Please select a pensioner.',
            'pensioner_id.exists' => 'The selected pensioner does not exist.',

            'month.required' => 'Please provide the month.',
            'month.date' => 'The month must be a valid date.',

            'basic_pension.required' => 'Basic pension is required.',
            'basic_pension.numeric' => 'Basic pension must be a number.',

            'commutation_amount.required' => 'Commutation amount is required.',
            'commutation_amount.numeric' => 'Commutation amount must be numeric.',

            'additional_pension.required' => 'Additional pension is required.',
            'additional_pension.numeric' => 'Additional pension must be numeric.',

            'dr_id.exists' => 'The selected dearness relief is invalid.',

            'dr_amount.numeric' => 'Dearness relief amount must be numeric.',

            'medical_allowance.required' => 'Medical allowance is required.',
            'medical_allowance.numeric' => 'Medical allowance must be numeric.',

            'remarks.string' => 'Remarks must be a string.',
            'remarks.max' => 'Remarks may not be greater than 255 characters.',

            'status.required' => 'Status is required.',
            'status.in' => 'Status must be one of: Pending, Processed, or Paid.',
        ]);

        $formattedMonth = \Carbon\Carbon::parse($request->month)->startOfMonth()->format('Y-m-d');

        // Calculate totals
        $drAmount = $request->dr_amount ?? 0;
        $totalPension = $request->basic_pension + $request->additional_pension + $drAmount + $request->medical_allowance;
        $totalRecovery = $request->commutation_amount;
        $netPension = $totalPension - $totalRecovery;

        $data->pensioner_id = $request['pensioner_id'];
        $data->month = $formattedMonth;
        $data->basic_pension = $request['basic_pension'];
        $data->commutation_amount = $request['commutation_amount'];
        $data->additional_pension = $request['additional_pension'];
        $data->dr_id = $request['dr_id'];
        $data->dr_amount = $drAmount;
        $data->medical_allowance = $request['medical_allowance'];
        $data->total_pension = $totalPension;
        $data->total_recovery = $totalRecovery;
        $data->net_pension = $netPension;
        $data->remarks = $request['remarks'];
        $data->status = $request['status'];
        $data->edited_by = auth()->id();

        try {
            $data->save();
            return response()->json([
                'successMsg' => 'Monthly Pension update successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'errorMsg' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
