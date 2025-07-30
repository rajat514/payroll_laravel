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

        $query = MonthlyPension::with('netPension', 'dearness', 'pensionRelatedInfo', 'netPension.pensioner.employee');

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

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
            'status' => 'required|in:Initiated,Approved,Disbursed',

            'income_tax' => 'nullable|numeric',
            'recovery' => 'nullable|numeric',
            'other' => 'nullable|numeric',
            'description' => 'nullable|string'
        ]);

        $hasNetPension = NetPension::where('pensioner_id', $request['pensioner_id'])->where('month', $request['month'])->where('year', $request['year'])->get()->first();
        if ($hasNetPension) return response()->json(['errorMsg' => 'This Month Pension is already created!'], 400);

        $pensionInfo = PensionRelatedInfo::find($request['pension_related_info_id']);
        if (!$pensionInfo) return response()->json(['errorMsg' => 'Pension Related Information not found!'], 404);

        $drRate = DearnessRelief::find($request['dr_id']);
        if (!$drRate) return response()->json(['errorMsg' => 'Dearness Relief not found!'], 404);

        $pensionBank = BankAccount::where('pensioner_id', $request['pensioner_id'])->where('is_active', 1)->first();
        if (!$pensionBank) return response()->json(['errorMsg', 'Pensioner Bank not found!'], 404);

        DB::beginTransaction();

        $drAmount = ($pensionInfo->basic_pension + $pensionInfo->additional_pension) * $drRate->dr_percentage / 100;

        // Calculate totals
        $total = $pensionInfo->basic_pension +
            $pensionInfo->additional_pension +
            $drAmount +
            $pensionInfo->medical_allowance +
            $pensionInfo->total_arrear;

        $total_deduction = $pensionInfo->commutation_amount + $request['income_tax'] + $request['recovery'] + $request['other'];

        $netPension = new NetPension();
        $netPension->pensioner_id = $request['pensioner_id'];
        $netPension->pensioner_bank_id = $request['pensioner_bank_id'];
        $netPension->month = $request['month'];
        $netPension->year = $request['year'];
        $netPension->processing_date = $request['processing_date'];
        $netPension->payment_date = $request['payment_date'];
        $netPension->net_pension = $total - $total_deduction;
        $netPension->added_by = auth()->id();

        try {
            $netPension->save();

            $monthlyPension = $netPension->monthlyPension()->create([
                'pension_rel_info_id' => $request['pension_related_info_id'],
                'basic_pension' => $pensionInfo->basic_pension,
                'additional_pension' => $pensionInfo->aditional_pension,
                'dr_id' => $request['dr_id'],
                'dr_amount' => $drAmount,
                'medical_allowance' => $pensionInfo->medical_allowance,
                'total_arrear' => $pensionInfo->total_arrear,
                'total_pension' => $total,
                'remarks' =>  $request['remarks'],
                'status' =>  $request['status'],
                'added_by' =>  auth()->id(),
            ]);

            $pensionDeduction = $netPension->pensionerDeduction()->create([
                'income_tax' => $request['income_tax'],
                'commutation_amount' => $pensionInfo->commutation_amount,
                'recovery' => $request['recovery'],
                'other' => $request['other'],
                'description' => $request['description'],
                'amount' => $total_deduction,
                'added_by' => auth()->id()
            ]);

            DB::commit();
            return response()->json(['successMsg' => 'Monthly Pension create successfully!', 'data' => [$netPension, $monthlyPension, $pensionDeduction]], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = MonthlyPension::with(
            'history.addedBy.roles:id,name',
            'history.editedBy.roles:id,name',
            'addedBy.roles:id,name',
            'editedBy.roles:id,name',
            'history.netPension',
            'history.netPension.pensioner',
            'history.dearness',
            'netPension.pensioner.employee'
        )->find($id);

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
            'status' => 'required|in:Initiated,Approved,Disbursed',

            'basic_pension' => 'required|numeric',
            'commutation_amount' => 'nullable|numeric',
            'is_active' => 'boolean|in:0,1',
            'additional_pension' => 'nullable|integer',
            'medical_allowance' => 'nullable|integer',
            'arrear_type' => 'nullable|string',
            'total_arrear' => 'nullable|numeric',
            'arrear_remarks' => 'nullable|string',
            'remarks' => 'nullable|max:255',
        ]);

        // $hasNetPension = MonthlyPension::where('net_pension_id')->get()->first();
        // if ($hasNetPension) return response()->json(['errorMsg' => 'This net pension is already added in monthly pension!. Please select another net pension.'], 400);

        $netPension = NetPension::with('pensionerDeduction')->find($request['net_pension_id']);
        if (!$netPension) return response()->json(['errorMsg' => 'Net Pension not found!'], 404);

        $pensionInfo = PensionRelatedInfo::find($request['pension_related_info_id']);
        if (!$pensionInfo) return response()->json(['errorMsg' => 'Monthly pension not found!'], 404);

        $drRate = DearnessRelief::find($request['dr_id']);
        if (!$drRate) return response()->json(['errorMsg' => 'Dearness Relief not found!'], 404);


        DB::beginTransaction();

        $net_pension_old_data = $netPension->toArray();
        $net_pension_old_data['pension_rel_info_id'] = $monthlyPension->pension_rel_info_id;
        $net_pension_old_data['basic_pension'] = $monthlyPension->basic_pension;
        $net_pension_old_data['additional_pension'] = $monthlyPension->additional_pension;
        $net_pension_old_data['dr_id'] = $monthlyPension->dr_id;
        $net_pension_old_data['dr_amount'] = $monthlyPension->dr_amount;
        $net_pension_old_data['medical_allowance'] = $monthlyPension->medical_allowance;
        $net_pension_old_data['total_arrear'] = $monthlyPension->total_arrear;
        $net_pension_old_data['total_pension'] = $monthlyPension->total_pension;
        $net_pension_old_data['remarks'] = $monthlyPension->remarks;
        $net_pension_old_data['status'] = $monthlyPension->status;
        $net_pension_old_data['commutation_amount'] = $netPension->pensionerDeduction->commutation_amount;
        $net_pension_old_data['income_tax'] = $netPension->pensionerDeduction->income_tax;
        $net_pension_old_data['recovery'] = $netPension->pensionerDeduction->recovery;
        $net_pension_old_data['other'] = $netPension->pensionerDeduction->other;
        $net_pension_old_data['amount'] = $netPension->pensionerDeduction->amount;
        $net_pension_old_data['description'] = $netPension->pensionerDeduction->description;

        $old_data = $monthlyPension->toArray();

        $pensionInfo->basic_pension = $request['basic_pension'];
        $pensionInfo->commutation_amount = $request['commutation_amount'];
        $pensionInfo->is_active = $request['is_active'];
        $pensionInfo->additional_pension = $request['additional_pension'];
        $pensionInfo->medical_allowance = $request['medical_allowance'];
        $pensionInfo->arrear_type = $request['arrear_type'];
        $pensionInfo->total_arrear = $request['total_arrear'];
        $pensionInfo->arrear_remarks = $request['arrear_remarks'];
        $pensionInfo->remarks = $request['remarks'];
        $pensionInfo->edited_by = auth()->id();

        $drAmount = ($pensionInfo->basic_pension + $pensionInfo->additional_pension) * $drRate->dr_percentage / 100;

        $total = $pensionInfo->basic_pension +
            $pensionInfo->additional_pension +
            $drAmount +
            $pensionInfo->medical_allowance +
            $pensionInfo->total_arrear;


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
            $pensionInfo->save();

            $monthlyPension->save();

            if ($netPension->pensionerDeduction) {
                $netPension->net_pension = $monthlyPension->total_pension - $netPension->pensionerDeduction->amount;
            }
            $netPension->edited_by = auth()->id();

            $netPension->save();

            $net_pension_clone = $netPension->history()->create($net_pension_old_data);
            $old_data['net_pension_clone_id'] = $net_pension_clone->id;

            $monthlyPension->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Monthly Pension update successfully!', 'data' => $monthlyPension], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
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

        $lastVerifiedEmployee = NetPension::with('monthlyPension', 'pensionerDeduction')->where('month', $previousMonth - 1)->where('year', $previousYear)->get(); //->where('is_verified', 1)

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
                    'additional_pension' => $pensionerData->monthlyPension->aditional_pension,
                    'dr_id' => $pensionerData->monthlyPension->dr_id,
                    'dr_amount' => $pensionerData->monthlyPension->dr_amount,
                    'medical_allowance' => $pensionerData->monthlyPension->medical_allowance,
                    'total_arrear' => $pensionerData->monthlyPension->total_arrear,
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
}
