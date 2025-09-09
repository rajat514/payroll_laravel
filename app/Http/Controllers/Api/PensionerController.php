<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Employee;
use App\Models\EmployeeStatus;
use App\Models\PensionerInformation;
use App\Models\PensionRelatedInfo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PensionerController extends Controller
{

    private \App\Models\User $user;

    private $all_permission_roles = ['IT Admin', 'Director', 'Pensioners Operator'];
    private $can_add_roles = ['IT Admin', 'Director', 'Pensioners Operator'];
    private $can_update_roles = ['IT Admin', 'Director', 'Pensioners Operator'];
    private $can_view_roles = ['IT Admin', 'Director', 'Pensioners Operator', 'Administrative Officer', 'Account Officer'];



    function __construct()
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

        $query = PensionerInformation::with('employee', 'addedBy.roles:id,name', 'editedBy.roles:id,name', 'user', 'bankAccount');

        $query->when(
            request('retired_employee_id'),
            fn($q) => $q->where('retired_employee_id', request('retired_employee_id'))
        );

        $query->when(
            request('search'),
            fn($q) => $q->where('first_name', 'LIKE', '%' . request('search') . '%')
                ->orwhere('middle_name', 'LIKE', '%' . request('search') . '%')
                ->orwhere('last_name', 'LIKE', '%' . request('search') . '%')
                ->orwhere('ppo_no', 'LIKE', '%' . request('search') . '%')
        );

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
            'ppo_no' => 'required|string|max:20',
            'first_name' => 'required|string|max:100',
            'type_of_pension' => 'required|in:Regular,Family',
            'retired_employee_id' => 'nullable',
            'user_id' => 'nullable|exists:users,id',
            'relation' => 'required|in:Self,Spouse,Son,Daughter,Other',
            'dob' => 'nullable|date',
            'doj' => 'nullable|date',
            'dor' => 'nullable|date',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'status' => 'required|in:Active,Inactive',
            'pan_number' => 'nullable|string|max:10',
            'pay_level' => 'nullable|string|max:50',
            'pay_commission' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'pin_code' => 'nullable|string|max:10',
            'mobile_no' => 'nullable|string|max:15',
            'email' => 'nullable|email',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'pay_cell' => 'nullable|string|max:50',
            'pay_commission_at_retirement' => 'nullable|string|max:100',
            'basic_pay_at_retirement' => 'nullable|integer',
            'last_drawn_salary' => 'nullable|integer',
            'NPA' => 'nullable|integer',
            'HRA' => 'nullable|integer',
            'special_pay' => 'nullable|integer',

            // Bank fields
            'bank_name' => 'required|string|max:100',
            'branch_name' => 'required|string|max:100',
            'account_no' => 'required|string|unique:bank_accounts,account_no',
            'ifsc_code' => 'required|string|max:20|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',

            // Pension info fields
            'basic_pension' => 'required|numeric',
            'commutation_amount' => 'nullable|numeric',
            'additional_pension' => 'nullable|integer',
            'medical_allowance' => 'nullable|integer',
            'arrear_type' => 'nullable|string',
            'total_arrear' => 'nullable|numeric',
            'arrear_remarks' => 'nullable|string',
            'remarks' => 'nullable|max:255',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
        ]);

        try {
            DB::beginTransaction();

            // Ensure user is retired
            $user = User::find($request['user_id']);
            if (!$user->is_retired) {
                return response()->json(['errorMsg' => 'The provided user is not retired.'], 400);
            }

            // 1. Create Pensioner
            $pensioner = new PensionerInformation([
                'ppo_no' => $request['ppo_no'],
                'first_name' => $request['first_name'],
                'type_of_pension' => $request['type_of_pension'],
                'retired_employee_id' => $request['retired_employee_id'],
                'user_id' => $request['user_id'],
                'relation' => $request['relation'],
                'dob' => $request['dob'],
                'doj' => $request['doj'],
                'dor' => $request['dor'],
                'start_date' => $request['start_date'],
                'end_date' => $request['end_date'],
                'status' => $request['status'],
                'pan_number' => $request['pan_number'],
                'pay_level' => $request['pay_level'],
                'pay_commission' => $request['pay_commission'],
                'address' => $request['address'],
                'city' => $request['city'],
                'state' => $request['state'],
                'pin_code' => $request['pin_code'],
                'mobile_no' => $request['mobile_no'],
                'email' => $request['email'],
                'middle_name' => $request['middle_name'],
                'last_name' => $request['last_name'],
                'pay_cell' => $request['pay_cell'],
                'pay_commission_at_retirement' => $request['pay_commission_at_retirement'],
                'basic_pay_at_retirement' => $request['basic_pay_at_retirement'],
                'last_drawn_salary' => $request['last_drawn_salary'],
                'NPA' => $request['NPA'],
                'HRA' => $request['HRA'],
                'special_pay' => $request['special_pay'],
                'added_by' => auth()->id(),
            ]);
            $pensioner->save();

            // 2. Create Bank Account
            $bank = new BankAccount([
                'pensioner_id' => $pensioner->id,
                'bank_name' => $request['bank_name'],
                'branch_name' => $request['branch_name'],
                'account_no' => $request['account_no'],
                'ifsc_code' => $request['ifsc_code'],
                'is_active' => 1,
                'added_by' => auth()->id(),
            ]);
            $bank->save();

            // Mark other accounts inactive (optional if this is first entry)
            BankAccount::where('pensioner_id', $pensioner->id)->where('id', '<>', $bank->id)->update(['is_active' => 0]);

            // 3. Create Pension Info
            $pensionInfo = new PensionRelatedInfo([
                'pensioner_id' => $pensioner->id,
                'basic_pension' => $request['basic_pension'],
                'commutation_amount' => $request['commutation_amount'],
                'is_active' => 1,
                'additional_pension' => $request['additional_pension'],
                'medical_allowance' => $request['medical_allowance'],
                'arrear_type' => $request['arrear_type'],
                'total_arrear' => $request['total_arrear'],
                'arrear_remarks' => $request['arrear_remarks'],
                'remarks' => $request['remarks'],
                'effective_from' => $request['effective_from'],
                'effective_till' => $request['effective_till'],
                'added_by' => auth()->id(),
            ]);
            $pensionInfo->save();

            DB::commit();

            return response()->json([
                'successMsg' => 'Pensioner, Bank Account, and Pension Info created successfully!',
                'data' => [
                    'pensioner' => $pensioner,
                    'bank' => $bank,
                    'pension_info' => $pensionInfo
                ]
            ]);
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
        // if (!$this->user->hasAnyRole($this->can_view_roles)) {
        //     return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        // }

        $pensioner = PensionerInformation::with(
            'history.addedBy.roles:id,name',
            'history.editedBy.roles:id,name',
            'history.employee',
            'addedBy.roles:id,name',
            'editedBy.roles:id,name',
            'bankAccount',
            'pensionRelatedInfo',
            'document',
            'user.employee'
        )->find($id);

        return response()->json([
            'message' => 'Fetch pensioner data successfully',
            'data' => $pensioner
        ], 200);
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
        // if (!$this->user->hasAnyRole($this->can_update_roles)) {
        //     return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        // }

        $pensioner = PensionerInformation::find($id);

        if (!$pensioner) return response()->json(['message' => 'Pensioner data not found!'], 404);

        $request->validate([
            'ppo_no' => "required|string|max:20|unique:pensioner_information,id,$id,id",
            'first_name' => 'required|string|max:100',
            'type_of_pension' => 'required|in:Regular,Family',
            'retired_employee_id' => 'nullable',
            'user_id' => 'nullable|exists:users,id',
            'relation' => 'nullable|in:Self,Spouse,Son,Daughter,Other',
            'dob' => 'nullable|date',
            'doj' => 'nullable|date|after:dob',
            'dor' => 'nullable|date|after:dob|after:doj',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'status' => 'required|in:Active,Inactive',
            'pan_number' => 'nullable|string|max:10',
            'pay_level' => 'nullable|string|max:50',
            'pay_commission' => 'nullable|string|max:50',
            // 'equivalent_level' => 'required|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'pin_code' => 'nullable|string|max:10',
            'mobile_no' => 'nullable|string|max:15',
            'email' => 'nullable|email',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'pay_cell' => 'nullable|string|max:50',
            'pay_commission_at_retirement' => 'nullable|string|max:100',
            'basic_pay_at_retirement' => 'nullable|integer',
            'last_drawn_salary' => 'nullable|integer',
            'NPA' => 'nullable|integer',
            'HRA' => 'nullable|integer',
            'special_pay' => 'nullable|integer',
        ]);

        // Check if employee exists and is retired
        $user = User::find($request['user_id']);
        // Check if employee exists and is retired
        if (!$user->is_retired) {
            return response()->json(['errorMsg' => 'The provided user is either not found or not retired.'], 400);
        }

        DB::beginTransaction();

        $old_data  = $pensioner->toArray();

        $pensioner->ppo_no = $request['ppo_no'];
        $pensioner->first_name = $request['first_name'];
        $pensioner->type_of_pension = $request['type_of_pension'];
        $pensioner->user_id = $request['user_id'];
        $pensioner->retired_employee_id = $request['retired_employee_id'];
        $pensioner->relation = $request['relation'];
        $pensioner->dob = $request['dob'];
        $pensioner->doj = $request['doj'];
        $pensioner->dor = $request['dor'];
        $pensioner->start_date = $request['start_date'];
        $pensioner->end_date = $request['end_date'];
        $pensioner->status = $request['status'];
        $pensioner->pan_number = $request['pan_number'];
        $pensioner->pay_level = $request['pay_level'];
        $pensioner->pay_commission = $request['pay_commission'];
        // $pensioner->equivalent_level = $request['equivalent_level'];
        $pensioner->address = $request['address'];
        $pensioner->city = $request['city'];
        $pensioner->state = $request['state'];
        $pensioner->pin_code = $request['pin_code'];
        $pensioner->mobile_no = $request['mobile_no'];
        $pensioner->email = $request['email'];
        $pensioner->last_name = $request['last_name'];
        $pensioner->middle_name = $request['middle_name'];
        $pensioner->pay_cell = $request['pay_cell'];
        $pensioner->pay_commission_at_retirement = $request['pay_commission_at_retirement'];
        $pensioner->basic_pay_at_retirement = $request['basic_pay_at_retirement'];
        $pensioner->last_drawn_salary = $request['last_drawn_salary'];
        $pensioner->NPA = $request['NPA'];
        $pensioner->HRA = $request['HRA'];
        $pensioner->special_pay = $request['special_pay'];
        $pensioner->edited_by = auth()->id();

        try {
            $pensioner->save();

            $pensioner->history()->create($old_data);

            DB::commit();
            return response()->json([
                'message' => 'Pensioner detail updated successfully!',
                'data' => $pensioner
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
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

    public function changeStatus(Request $request, $id)
    {
        // if (!$this->user->hasAnyRole($this->can_update_roles)) {
        //     return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        // }

        $pensioner = PensionerInformation::find($id);

        if (!$pensioner) return response()->json(['message' => 'Pensioner data not found!'], 404);

        $request->validate([
            'status' => 'required|in:Active,Inactive'
        ]);

        DB::beginTransaction();

        $old_data = $pensioner->toArray();

        $pensioner->status = $request['status'];

        try {
            $pensioner->save();

            $pensioner->history()->create($old_data);

            DB::commit();
            return response()->json(['message' => 'Pensioner status change successfully!', 'data' => $pensioner], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
