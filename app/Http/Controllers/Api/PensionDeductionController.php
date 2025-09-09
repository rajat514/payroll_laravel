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
    private \App\Models\User $user;

    private $all_permission_roles = ['IT Admin', 'Director', 'Pensioners Operator'];
    private $can_add_roles = ['IT Admin', 'Director', 'Pensioners Operator'];
    private $can_update_roles = ['IT Admin', 'Director', 'Pensioners Operator'];
    private $can_view_roles = ['IT Admin', 'Director', 'Pensioners Operator', 'Administrative Officer'];

    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = \App\Models\User::find(auth()->id());
            return $next($request);
        });
    }

    public function index()
    {
        // if (!$this->user->hasAnyRole($this->can_view_roles)) {
        //     return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        // }

        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = PensionDeduction::with('addedBy.roles:id,name', 'editedBy.roles:id,name', 'netPension');

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

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
        // if (!$this->user->hasAnyRole($this->can_add_roles)) {
        //     return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        // }

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
        // if (!$this->user->hasAnyRole($this->can_view_roles)) {
        //     return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        // }

        $data = PensionDeduction::with('history.addedBy.roles:id,name', 'history.editedBy.roles:id,name', 'addedBy.roles:id,name', 'editedBy.roles:id,name', 'netPension.pensioner.employee', 'history.netPension.pensioner')->find($id);

        return response()->json(['data' => [$data, $this->user]]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // if (!$this->user->hasAnyRole($this->can_update_roles)) {
        //     return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        // }

        $data = PensionDeduction::find($id);
        if (!$data) return response()->json(['errorMsg' => 'Pension deduction not found!'], 404);

        $request->validate([
            'net_pension_id' => 'required|exists:net_pensions,id',
            'income_tax' => 'nullable|numeric',
            'commutation_amount' => 'nullable|numeric',
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


        DB::beginTransaction();

        $net_pension_old_data = $netPension->toArray();
        $net_pension_old_data['pension_rel_info_id'] = $netPension->monthlyPension->pension_rel_info_id;
        $net_pension_old_data['basic_pension'] = $netPension->monthlyPension->basic_pension;
        $net_pension_old_data['additional_pension'] = $netPension->monthlyPension->additional_pension;
        $net_pension_old_data['dr_id'] = $netPension->monthlyPension->dr_id;
        $net_pension_old_data['dr_amount'] = $netPension->monthlyPension->dr_amount;
        $net_pension_old_data['medical_allowance'] = $netPension->monthlyPension->medical_allowance;
        $net_pension_old_data['total_arrear'] = $netPension->monthlyPension->total_arrear;
        $net_pension_old_data['total_pension'] = $netPension->monthlyPension->total_pension;
        $net_pension_old_data['arrears'] = $netPension->monthlyPension->arrears ? json_encode($netPension->monthlyPension->arrears) : null;
        $net_pension_old_data['remarks'] = $netPension->monthlyPension->remarks;
        $net_pension_old_data['status'] = $netPension->monthlyPension->status;
        $net_pension_old_data['commutation_amount'] = $data->commutation_amount;
        $net_pension_old_data['income_tax'] = $data->income_tax;
        $net_pension_old_data['recovery'] = $data->recovery;
        $net_pension_old_data['other'] = $data->other;
        $net_pension_old_data['amount'] = $data->amount;
        $net_pension_old_data['description'] = $data->description;

        $old_data = $data->toArray();

        $pensionInfo->commutation_amount = $request['commutation_amount'];
        $pensionInfo->edited_by = auth()->id();

        try {
            $pensionInfo->save();
        } catch (\Exception $e) {
            return response()->json(['errorMsg', $e->getMessage()], 500);
        }

        $total = $pensionInfo->commutation_amount + $request['income_tax'] + $request['recovery'] + $request['other'];

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

            $netPension->net_pension = $netPension->monthlyPension->total_pension - $total;
            $netPension->edited_by = auth()->id();

            $netPension->save();

            $net_pension_clone = $netPension->history()->create($net_pension_old_data);
            $old_data['net_pension_clone_id'] = $net_pension_clone->id;

            $data->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Pension Deduction updated successfully!', 'data' => $data], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage(),], 500);
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
