<?php

namespace App\Http\Controllers\cron_api;

use App\Http\Controllers\Controller;
use App\Models\DearnessRelief;
use App\Models\NetPension;
use App\Models\PensionRelatedInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



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

    $lastVerifiedEmployee = NetPension::with('monthlyPension', 'pensionerDeduction')
        ->where('month', $previousMonth - 1)
        ->where('is_verified', 1)
        ->where('year', $previousYear)->get(); //->where('is_verified', 1)

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
        $netPension->net_pension = $pensionerData->net_pension;

        try {
            $netPension->save();

            $netPension->monthlyPension()->create([
                'pension_rel_info_id' => $pensionerData->monthlyPension->pension_rel_info_id,
                'basic_pension' => $pensionerData->monthlyPension->basic_pension,
                'additional_pension' => $pensionerData->monthlyPension->additional_pension,
                'dr_id' => $pensionerData->monthlyPension->dr_id,
                'dr_amount' => $pensionerData->monthlyPension->dr_amount,
                'medical_allowance' => $pensionerData->monthlyPension->medical_allowance,
                'total_arrear' => $pensionerData->monthlyPension->total_arrear,
                'arrears' => json_encode($pensionerData->monthlyPension->arrears),
                'total_pension' => $pensionerData->monthlyPension->total_pension,
                'remarks' => $pensionerData->monthlyPension->remarks,
                'status' =>  $pensionerData->monthlyPension->status,
                'added_by' =>  auth()->id(),
            ]);

            $netPension->pensionerDeduction()->create([
                'income_tax' => $pensionerData->pensionerDeduction->income_tax,
                'commutation_amount' => $pensionerData->pensionerDeduction->commutation_amount,
                'recovery' => $pensionerData->pensionerDeduction->recovery,
                'other' => $pensionerData->pensionerDeduction->other,
                'description' => $pensionerData->pensionerDeduction->description,
                'amount' => $pensionerData->pensionerDeduction->amount,
                'added_by' => auth()->id()
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
    return response()->json(['successMsg' => 'Bulk Monthly Pension create successfully!'], 200);
}
