<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NetPension;
use App\Models\NetSalary;
use App\Models\PensionDeduction;
use App\Models\PensionRelatedInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PensionDeductionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = PensionDeduction::with('netPension', 'addedBy.role', 'editedBy.role', 'netPension.pensioner');

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
            'net_pension_id' => 'required|exists:net_pensions,id',
            'income_tax' => 'nullable|numeric',
            'recovery' => 'nullable|numeric',
            'other' => 'nullable|numeric',
            'description' => 'nullable|string'
        ]);

        $hasNetPension = PensionDeduction::where('net_pension_id')->get()->first();
        if ($hasNetPension) return response()->json(['errorMsg' => 'This net pension is already added in deduction!. Please select another net pension.'], 400);

        $netPension = NetPension::with('monthlyPension')->find($request['net_pension_id']);
        if (!$netPension) return response()->json(['errorMsg' => 'Net Pension not found!'], 404);

        $pensionInfo = PensionRelatedInfo::find($netPension->monthlyPension->pension_rel_info_id);
        if (!$pensionInfo) return response()->json(['errorMsg' => 'Pension Related Information not found!'], 404);

        $total = $pensionInfo->commutation_amount + $request['income_tax'] + $request['recovery'] + $request['other'];

        $data = new PensionDeduction();
        $data->net_pension_id = $request['net_pension_id'];
        $data->commutation_amount = $pensionInfo->commutation_amount;
        $data->income_tax = $request['income_tax'];
        $data->recovery = $request['recovery'];
        $data->other = $request['other'];
        $data->amount = $total;
        $data->description = $request['description'];
        $data->added_by = auth()->id();

        try {
            $data->save();

            $netPension->net_pension = $netPension->monthlyPension->total_pension - $total;

            $netPension->save();
            return response()->json([
                'successMsg' => 'Pension Deduction create successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'errorMsg' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = PensionDeduction::with('addedBy', 'editedBy', 'history.addedBy', 'history.editedBy', 'history.netPension.pensioner')->find($id);

        return response()->json(['data' => $data]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = PensionDeduction::find($id);
        if (!$data) return response()->json(['errorMsg' => 'Pension deduction not found!'], 404);

        $request->validate([
            'net_pension_id' => 'required|exists:net_pensions,id',
            'income_tax' => 'nullable|numeric',
            'recovery' => 'nullable|numeric',
            'other' => 'nullable|numeric',
            'description' => 'nullable|string'
        ]);

        $hasNetPension = PensionDeduction::where('net_pension_id')->get()->first();
        if ($hasNetPension) return response()->json(['errorMsg' => 'This net pension is already added in deduction!. Please select another net pension.'], 400);

        $netPension = NetPension::with('monthlyPension')->find($request['net_pension_id']);
        if (!$netPension) return response()->json(['errorMsg' => 'Net Pension not found!'], 404);

        $pensionInfo = PensionRelatedInfo::find($netPension->monthlyPension->pension_rel_info_id);
        if (!$pensionInfo) return response()->json(['errorMsg' => 'Pension Related Information not found!'], 404);

        $total = $pensionInfo->commutation_amount + $request['income_tax'] + $request['recovery'] + $request['other'];

        DB::beginTransaction();

        $old_data = $data->toArray();

        $data->net_pension_id = $request['net_pension_id'];
        $data->commutation_amount = $pensionInfo->commutation_amount;
        $data->income_tax = $request['income_tax'];
        $data->recovery = $request['recovery'];
        $data->other = $request['other'];
        $data->amount = $total;
        $data->description = $request['description'];
        $data->edited_by = auth()->id();

        try {
            $data->save();

            $data->history()->create($old_data);

            $netPension->net_pension = $netPension->monthlyPension->total_pension - $total;

            $netPension->save();

            DB::commit();
            return response()->json([
                'successMsg' => 'Pension Deduction updated successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'errorMsg' => $e->getMessage(),
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
