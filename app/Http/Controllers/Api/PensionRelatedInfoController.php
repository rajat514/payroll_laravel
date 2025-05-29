<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Arrears;
use App\Models\PensionRelatedInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PensionRelatedInfoController extends Controller
{
    public function index()
    {

        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = PensionRelatedInfo::query();

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        $request->validate([
            'pensioner_id' => 'nullable|numeric|exists:pensioner_information,id',
            'basic_pension' => 'required|numeric',
            'commutation_amount' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date||after:effective_from',
            'is_active' => 'boolean|in:0,1',
            'additional_pension' => 'nullable|integer',
            'medical_allawance' => 'nullable|integer',
            'arrear_id' => 'nullable|numeric|exists:arrears,id',
            'remarks' => 'nullable|max:255',
        ]);

        $arrear = Arrears::find($request['arrear_id']);
        if (!$arrear) return response()->json(['errorMsg' => 'Arrear not found!'], 404);

        $pensionInfo = new PensionRelatedInfo();
        $pensionInfo->basic_pension = $request['basic_pension'];
        $pensionInfo->pensioner_id = $request['pensioner_id'];
        $pensionInfo->commutation_amount = $request['commutation_amount'];
        $pensionInfo->effective_from = $request['effective_from'];
        $pensionInfo->effective_till = $request['effective_till'];
        $pensionInfo->is_active = $request['is_active'];
        $pensionInfo->additional_pension = $request['additional_pension'];
        $pensionInfo->medical_allowance = $request['medical_allowance'];
        $pensionInfo->arrear_id = $request['arrear_id'];
        $pensionInfo->total_arrear = $arrear->total_arrear;
        $pensionInfo->remarks = $request['remarks'];
        $pensionInfo->added_by = auth()->id();

        try {
            $pensionInfo->save();
            return response()->json(['successMsg' => 'Pension Related Information successfully added!', 'data' => $pensionInfo]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $pensionInfo = PensionRelatedInfo::find($id);
        if (!$pensionInfo) return response()->json(['errorMsg' => 'Pension Related Information not found!'], 404);

        $request->validate([
            'pensioner_id' => 'nullable|numeric|exists:pensioner_information,id',
            'basic_pension' => 'required|numeric',
            'commutation_amount' => 'required|numeric',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date',
            'is_active' => 'boolean|in:0,1',
            'additional_pension' => 'nullable|integer',
            'medical_allawance' => 'nullable|integer',
            'arrear_id' => 'nullable|numeric|exists:arrears,id',
            'remarks' => 'nullable|max:255',
        ]);

        $arrear = Arrears::find($request['arrear_id']);
        if (!$arrear) return response()->json(['errorMsg' => 'Arrear not found!'], 404);

        DB::beginTransaction();

        $old_data = $pensionInfo->toArray();

        $pensionInfo->basic_pension = $request['basic_pension'];
        $pensionInfo->pensioner_id = $request['pensioner_id'];
        $pensionInfo->commutation_amount = $request['commutation_amount'];
        $pensionInfo->effective_from = $request['effective_from'];
        $pensionInfo->effective_till = $request['effective_till'];
        $pensionInfo->is_active = $request['is_active'];
        $pensionInfo->additional_pension = $request['additional_pension'];
        $pensionInfo->medical_allowance = $request['medical_allowance'];
        $pensionInfo->arrear_id = $request['arrear_id'];
        $pensionInfo->total_arrear = $arrear->total_arrear;
        $pensionInfo->remarks = $request['remarks'];
        $pensionInfo->edited_by = auth()->id();

        try {
            $pensionInfo->save();

            $pensionInfo->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Pension Related Information successfully updated!', 'data' => $pensionInfo]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function show($id)
    {
        $data = PensionRelatedInfo::with('addedBy', 'editedBy', 'history.addedBy', 'history.editedBy')->find($id);

        return response()->json(['data' => $data]);
    }
}
