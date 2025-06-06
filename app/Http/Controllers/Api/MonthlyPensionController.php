<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\DearnessRelief;
use App\Models\MonthlyPension;
use App\Models\NetPension;
use App\Models\PensionDeduction;
use App\Models\PensionRelatedInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $query = MonthlyPension::with('netPension', 'dearness', 'pensionRelatedInfo', 'netPension.pensioner');

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'pensioner_id' => 'required|exists:pensioner_information,id',
            'pensioner_bank_id' => 'required|exists:bank_accounts,id',
            'month' => 'required|numeric|max:12|min:1',
            'year' => 'required|numeric|digits:4|min:1900',
            'processing_date' => 'required|date',
            'payment_date' => 'nullable|date|after:processing_date',


            'pension_related_info_id' => 'required|exists:pension_related_infos,id',
            'dr_id' => 'nullable|exists:dearness_reliefs,id',
            'remarks' => 'nullable|string|max:255',
            'status' => 'required|in:Pending,Processed,Paid',
        ]);

        $hasNetPension = MonthlyPension::where('net_pension_id')->get()->first();
        if ($hasNetPension) return response()->json(['errorMsg' => 'This net pension is already added in monthly pension!. Please select another net pension.'], 400);

        $pensionInfo = PensionRelatedInfo::find($request['pension_related_info_id']);
        if (!$pensionInfo) return response()->json(['errorMsg' => 'Pension Related Information not found!'], 404);

        $drRate = DearnessRelief::find($request['dr_id']);
        if (!$drRate) return response()->json(['errorMsg' => 'Dearness Relief not found!'], 404);

        $pensionBank = BankAccount::where('pensioner_id', $request['pensioner_id'])->where('is_active', 1)->orderBy('created_at', 'DESC')->first();
        if (!$pensionBank) return response()->json(['errorMsg', 'Pensioner Bank not found!'], 404);

        DB::beginTransaction();

        $drAmount = ($pensionInfo->basic_pension + $pensionInfo->additional_pension) * $drRate->dr_percentage / 100;

        // Calculate totals
        $total = $pensionInfo->basic_pension + $pensionInfo->additional_pension + $drAmount + $pensionInfo->medical_allowance + $pensionInfo->total_arrear;

        $netPension = new NetPension();
        $netPension->pensioner_id = $request['pensioner_id'];
        $netPension->pensioner_bank_id = $request['pensioner_bank_id'];
        $netPension->month = $request['month'];
        $netPension->year = $request['year'];
        $netPension->processing_date = $request['processing_date'];
        $netPension->payment_date = $request['payment_date'];
        $netPension->net_pension = $total;

        try {
            $netPension->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }


        $data = new MonthlyPension();
        $data->net_pension_id = $netPension->id;
        $data->pension_rel_info_id = $request['pension_related_info_id'];
        $data->basic_pension = $pensionInfo->basic_pension;
        $data->additional_pension = $pensionInfo->aditional_pension;
        $data->dr_id = $request['dr_id'];
        $data->dr_amount = $drAmount;
        $data->medical_allowance = $pensionInfo->medical_allowance;
        $data->total_arrear = $pensionInfo->total_arrear;
        $data->total_pension = $total;
        $data->remarks = $request['remarks'];
        $data->status = $request['status'];
        $data->added_by = auth()->id();

        try {
            $data->save();
            DB::commit();
            return response()->json(['successMsg' => 'Monthly Pension create successfully!', 'data' => $data], 200);
        } catch (\Exception $e) {
            DB::rollBack();
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
        $data = MonthlyPension::with('addedBy', 'editedBy', 'history.addedBy', 'history.editedBy', 'history.netPension', 'history.netPension.pensioner', 'history.dearness')->find($id);

        return response()->json(['data' => $data]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $monthlyPension = MonthlyPension::find($id);
        if (!$monthlyPension) return response()->json(['errorMsg' => 'Monthly Pension not found!'], 404);


        $request->validate([
            'net_pension_id' => 'required|exists:net_pensions,id',
            'pension_related_info_id' => 'required|exists:pension_related_infos,id',
            'dr_id' => 'nullable|exists:dearness_reliefs,id',
            'remarks' => 'nullable|string|max:255',
            'status' => 'required|in:Pending,Processed,Paid',
        ]);

        $hasNetPension = MonthlyPension::where('net_pension_id')->get()->first();
        if ($hasNetPension) return response()->json(['errorMsg' => 'This net pension is already added in monthly pension!. Please select another net pension.'], 400);

        $netPension = NetPension::with('pensionerDeduction')->find($request['net_pension_id']);
        if (!$netPension) return response()->json(['errorMsg' => 'Net Pension not found!'], 404);

        $pensionInfo = PensionRelatedInfo::find($request['pension_related_info_id']);
        if (!$pensionInfo) return response()->json(['errorMsg' => 'Monthly pension not found!'], 404);

        $drRate = DearnessRelief::find($request['dr_id']);
        if (!$drRate) return response()->json(['errorMsg' => 'Dearness Relief not found!'], 404);

        $drAmount = ($pensionInfo->basic_pension + $pensionInfo->additional_pension) * $drRate->dr_percentage / 100;

        $total = $pensionInfo->basic_pension + $pensionInfo->additional_pension + $drAmount + $pensionInfo->medical_allowance + $pensionInfo->total_arrear;

        DB::beginTransaction();

        $old_data = $monthlyPension->toArray();

        $monthlyPension->net_pension_id = $request['net_pension_id'];
        $monthlyPension->pension_rel_info_id = $request['pension_related_info_id'];
        $monthlyPension->basic_pension = $pensionInfo->basic_pension;
        $monthlyPension->additional_pension = $pensionInfo->additional_pension;
        $monthlyPension->dr_id = $request['dr_id'];
        $monthlyPension->dr_amount = $drAmount;
        $monthlyPension->medical_allowance = $pensionInfo->medical_allowance;
        $monthlyPension->total_arrear = $pensionInfo->total_arrear;
        $monthlyPension->total_pension = $total;
        $monthlyPension->remarks = $request['remarks'];
        $monthlyPension->status = $request['status'];
        $monthlyPension->edited_by = auth()->id();

        try {
            $monthlyPension->save();

            if ($netPension->pensionerDeduction) {
                $netPension->net_pension = $monthlyPension->total_pension - $netPension->pensionerDeduction->amount;
            }

            $netPension->save();

            $monthlyPension->history()->create($old_data);

            DB::commit();
            return response()->json([
                'successMsg' => 'Monthly Pension update successfully!',
                'data' => $monthlyPension
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'errorMsg' => $e->getMessage()
            ], 500);
        }
    }

    function bulkPension(Request $request)
    {
        $request->validate([
            'month' => 'required|numeric|max:12|min:1',
            'year' => 'required|numeric|digits:4|min:1900',
            'processing_date' => 'required|date',
            'payment_date' => 'nullable|date|after:processing_date'
        ]);

        $previousMonth = $request['month'];
        $previousYear = $request['year'];
        if ($request['month'] == 1) {
            $previousMonth = 13;
            $previousYear -= 1;
        }

        $lastVerifiedEmployee = NetPension::with('monthlyPension')->where('month', $previousMonth - 1)->where('year', $previousYear)->get(); //->where('is_verified', 1)
        // return response()->json(['data' => $lastVerifiedEmployee]);

        // $hasNetPension = MonthlyPension::where('net_pension_id')->get()->first();
        // if ($hasNetPension) return response()->json(['errorMsg' => 'This net pension is already added in monthly pension!. Please select another net pension.'], 400);

        foreach ($lastVerifiedEmployee as $pensionerData) {
            $pensionInfo = PensionRelatedInfo::find($pensionerData->monthlyPension->pension_rel_info_id);
            if (!$pensionInfo) return response()->json(['errorMsg' => 'Pension Related Information not found!'], 404);

            $drRate = DearnessRelief::find($pensionerData->monthlyPension->dr_id);
            if (!$drRate) return response()->json(['errorMsg' => 'Dearness Relief not found!'], 404);

            // $pensionBank = BankAccount::where('pensioner_id', $request['pensioner_id'])->where('is_active', 1)->orderBy('created_at', 'DESC')->first();
            // if (!$pensionBank) return response()->json(['errorMsg', 'Pensioner Bank not found!'], 404);

            DB::beginTransaction();

            $drAmount = ($pensionInfo->basic_pension + $pensionInfo->additional_pension) * $drRate->dr_percentage / 100;

            // Calculate totals
            $total = $pensionInfo->basic_pension + $pensionInfo->additional_pension + $drAmount + $pensionInfo->medical_allowance + $pensionInfo->total_arrear;

            $netPension = new NetPension();
            $netPension->pensioner_id = $pensionerData->pensioner_id;
            $netPension->pensioner_bank_id = $pensionerData->pensioner_bank_id;
            $netPension->month = $request['month'];
            $netPension->year = $request['year'];
            $netPension->processing_date = $request['processing_date'];
            $netPension->payment_date = $request['payment_date'];
            $netPension->net_pension = $total;

            try {
                $netPension->save();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['errorMsg' => $e->getMessage()], 500);
            }


            $data = new MonthlyPension();
            $data->net_pension_id = $netPension->id;
            $data->pension_rel_info_id = $pensionerData->monthlyPension->pension_rel_info_id;
            $data->basic_pension = $pensionInfo->basic_pension;
            $data->additional_pension = $pensionInfo->aditional_pension;
            $data->dr_id = $drRate->id;
            $data->dr_amount = $drAmount;
            $data->medical_allowance = $pensionInfo->medical_allowance;
            $data->total_arrear = $pensionInfo->total_arrear;
            $data->total_pension = $total;
            $data->added_by = auth()->id();

            try {
                $data->save();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'errorMsg' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['successMsg' => 'Bulk Monthly Pension create successfully!'], 200);
    }
}
