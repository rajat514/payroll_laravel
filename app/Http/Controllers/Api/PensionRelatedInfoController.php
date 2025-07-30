<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Arrears;
use App\Models\PensionRelatedInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PensionRelatedInfoController extends Controller
{
    private \App\Models\User $user;

    private $all_permission_roles = ['IT Admin', 'Director', 'Pensioners Operator'];
    private $can_add_roles = ['IT Admin', 'Director', 'Pensioners Operator'];
    private $can_update_roles = ['IT Admin', 'Director', 'Pensioners Operator'];
    private $can_view_roles = ['IT Admin', 'Director', 'Pensioners Operator', ' Administrative Officer'];

    function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = \App\Models\User::find(auth()->id());
            return $next($request);
        });
    }

    public function index()
    {
        if (!$this->user->hasAnyRole($this->can_view_roles)) {
            return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        }

        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = PensionRelatedInfo::with('pensioner.employee');

        $query->when(
            request('pensioner_id'),
            fn($q) => $q->where('pensioner_id', request('pensioner_id'))
        );

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        if (!$this->user->hasAnyRole($this->can_add_roles)) {
            return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        }

        $request->validate([
            'pensioner_id' => 'required|numeric|exists:pensioner_information,id',
            'basic_pension' => 'required|numeric',
            'commutation_amount' => 'nullable|numeric',
            'is_active' => 'boolean|in:0,1',
            'additional_pension' => 'nullable|integer',
            'medical_allowance' => 'nullable|integer',
            'arrear_type' => 'nullable|string',
            'total_arrear' => 'nullable|numeric',
            'arrear_remarks' => 'nullable|string',
            'remarks' => 'nullable|max:255',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
        ]);

        // Set all previous records for this pensioner to inactive and update effective_till
        PensionRelatedInfo::where('pensioner_id', $request['pensioner_id'])
            ->update([
                'is_active' => 0,
            ]);

        $last_record = PensionRelatedInfo::where('pensioner_id', $request['pensioner_id'])
            ->orderBy('created_at', 'desc')
            ->first();
        if ($last_record) {
            $last_record->effective_till = \Carbon\Carbon::parse($request['effective_from'])->subDay()->format('Y-m-d');
            try {
                $last_record->save();
            } catch (\Exception $e) {
                return response()->json(['errorMsg' => $e->getMessage()], 400);
            }
        }

        // if ($request['is_active'] === 1) {
        //     $hasRelatedInfo = PensionRelatedInfo::where('pensioner_id', $request['pensioner_id'])
        //         ->where('is_active', 1)
        //         ->get();

        //     if ($hasRelatedInfo->isNotEmpty()) {
        //         foreach ($hasRelatedInfo as $info) {
        //             $info->is_active = 0;
        //             // $info->edited_by = auth()->id(); // Uncomment if needed
        //             try {
        //                 $info->save();
        //             } catch (\Throwable $e) {
        //                 return response()->json(['errorMsg' => $e->getMessage()], 400);
        //             }
        //         }
        //     }
        // }

        $pensionInfo = new PensionRelatedInfo();
        $pensionInfo->basic_pension = $request['basic_pension'];
        $pensionInfo->pensioner_id = $request['pensioner_id'];
        $pensionInfo->commutation_amount = $request['commutation_amount'];
        $pensionInfo->is_active = $request['is_active'];
        $pensionInfo->additional_pension = $request['additional_pension'];
        $pensionInfo->medical_allowance = $request['medical_allowance'];
        $pensionInfo->arrear_type = $request['arrear_type'];
        $pensionInfo->total_arrear = $request['total_arrear'];
        $pensionInfo->arrear_remarks = $request['arrear_remarks'];
        $pensionInfo->remarks = $request['remarks'];
        $pensionInfo->added_by = auth()->id();
        $pensionInfo->effective_from = $request['effective_from'];
        $pensionInfo->effective_till = $request['effective_till'];

        try {
            $pensionInfo->save();
            return response()->json(['successMsg' => 'Pension Related Information successfully added!', 'data' => $pensionInfo]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        if (!$this->user->hasAnyRole($this->can_update_roles)) {
            return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        }

        $pensionInfo = PensionRelatedInfo::find($id);
        if (!$pensionInfo) return response()->json(['errorMsg' => 'Pension Related Information not found!'], 404);

        $request->validate([
            'pensioner_id' => 'required|numeric|exists:pensioner_information,id',
            'basic_pension' => 'required|numeric',
            'commutation_amount' => 'nullable|numeric',
            'is_active' => 'boolean|in:0,1',
            'additional_pension' => 'nullable|integer',
            'medical_allowance' => 'nullable|integer',
            'arrear_type' => 'nullable|string',
            'total_arrear' => 'nullable|numeric',
            'arrear_remarks' => 'nullable|string',
            'remarks' => 'nullable|max:255',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
        ]);


        // if ($request['is_active'] === 1) {
        //     $hasRelatedInfo = PensionRelatedInfo::where('pensioner_id', $request['pensioner_id'])
        //         ->where('is_active', 1)
        //         ->get();

        //     if ($hasRelatedInfo->isNotEmpty()) {
        //         foreach ($hasRelatedInfo as $info) {
        //             $info->is_active = 0;
        //             // $info->edited_by = auth()->id(); // Uncomment if needed
        //             try {
        //                 $info->save();
        //             } catch (\Throwable $e) {
        //                 return response()->json(['errorMsg' => $e->getMessage()], 400);
        //             }
        //         }
        //     }
        // }

        // If setting this record to active, set all other records for this pensioner to inactive
        if ($request['is_active'] == 1) {
            PensionRelatedInfo::where('pensioner_id', $request['pensioner_id'])
                ->where('id', '!=', $id)
                ->update(['is_active' => 0]);
        }

        DB::beginTransaction();

        $old_data = $pensionInfo->toArray();

        $pensionInfo->basic_pension = $request['basic_pension'];
        $pensionInfo->pensioner_id = $request['pensioner_id'];
        $pensionInfo->commutation_amount = $request['commutation_amount'];
        $pensionInfo->is_active = $request['is_active'];
        $pensionInfo->additional_pension = $request['additional_pension'];
        $pensionInfo->medical_allowance = $request['medical_allowance'];
        $pensionInfo->arrear_type = $request['arrear_type'];
        $pensionInfo->total_arrear = $request['total_arrear'];
        $pensionInfo->arrear_remarks = $request['arrear_remarks'];
        $pensionInfo->remarks = $request['remarks'];
        $pensionInfo->edited_by = auth()->id();
        $pensionInfo->effective_from = $request['effective_from'];
        $pensionInfo->effective_till = $request['effective_till'];

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
        if (!$this->user->hasAnyRole($this->can_view_roles)) {
            return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        }

        $data = PensionRelatedInfo::with('history.addedBy.roles:id,name', 'history.editedBy.roles:id,name', 'addedBy.roles:id,name', 'editedBy.roles:id,name', 'pensioner.employee')->find($id);

        return response()->json(['data' => $data]);
    }
}
