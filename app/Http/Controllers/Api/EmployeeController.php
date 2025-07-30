<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\EmployeeClone;
use App\Models\EmployeeStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    private \App\Models\User $user;

    private $all_permission_roles = ['IT Admin', 'Director', 'Coordinator - NIOH', 'Coordinator - ROHC'];
    private $can_view_roles = ['IT Admin', 'Director', 'Coordinator - NIOH', 'Coordinator - ROHC', 'Administrative Officer', 'Account Officer'];
    private $view_own_roles = ['End User'];
    private $can_update_roles = ['IT Admin', 'Director', 'Coordinator - NIOH', 'Coordinator - ROHC'];

    function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = \App\Models\User::find(auth()->id());
            return $next($request);
        });
    }

    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = Employee::with('employeeStatus', 'user');

        $query->withCount('pensioner');

        $query->when(
            request('search'),
            fn($q) => $q->where('first_name', 'LIKE', '%' . request('search') . '%')
                ->orwhere('middle_name', 'LIKE', '%' . request('search') . '%')
                ->orwhere('last_name', 'LIKE', '%' . request('search') . '%')
                ->orwhere('employee_code', 'LIKE', '%' . request('search') . '%')
        );
        $query->when(
            $this->user->institute !== 'BOTH',
            fn($q) => $q->where('institute', $this->user->institute)
        );

        if ($this->user->institute === 'BOTH') {
            $query->when(
                request('institute'),
                fn($q) => $q->where('institute', request('institute'))
            );
        }

        $query->when(
            request('pwd_status') !== null,
            fn($q) => $q->where('pwd_status', request('pwd_status'))
        );

        $query->when(
            request('gis_eligibility') !== null,
            fn($q) => $q->where('gis_eligibility', request('gis_eligibility'))
        );

        $query->when(
            request('credit_society_member') !== null,
            fn($q) => $q->where('credit_society_member', request('credit_society_member'))
        );

        $query->when(
            request('uniform_allowance_eligibility') !== null,
            fn($q) => $q->where('uniform_allowance_eligibility', request('uniform_allowance_eligibility'))
        );

        $query->when(
            request('hra_eligibility') !== null,
            fn($q) => $q->where('hra_eligibility', request('hra_eligibility'))
        );

        $query->when(
            request('npa_eligibility') !== null,
            fn($q) => $q->where('npa_eligibility', request('npa_eligibility'))
        );

        $query->when(
            request('pension_scheme'),
            fn($q) => $q->where('pension_scheme', request('pension_scheme'))
        );

        $query->when(
            request('current_status'),
            fn($q) => $q->whereHas(
                'employeeStatus',
                fn($qn) => $qn->where('status', request('current_status'))
                    ->whereDate('effective_from', '<=', date('Y-m-d'))
                    ->whereDate('effective_till', '>=', date('Y-m-d'))
            )
        );

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        $request->validate(
            [
                'first_name' => 'required|string',
                'middle_name' => 'nullable|string',
                'last_name' => 'nullable|string',
                'user_id' => 'required|numeric|exists:users,id',
                'employee_code' => 'required|string|min:4|max:20|unique:employees,employee_code',
                'gender' => 'required|in:male,female,other',
                'prefix' => 'required|in:Mr.,Mrs.,Ms.,Dr.',
                'institute' => 'required|in:NIOH,ROHC,BOTH',
                'date_of_birth' => 'nullable|date',
                'date_of_joining' => 'nullable|date|after:date_of_birth',
                'date_of_retirement' => 'nullable|date|after:date_of_joining',
                'gis_eligibility' => 'nullable|in:1,0',
                'pwd_status' => 'required|in:1,0',
                'pension_scheme' => 'nullable|in:GPF,NPS',
                'pension_number' => 'nullable|string|min:2|max:191',
                'gis_no' => 'nullable|string|min:2|max:191',
                'credit_society_member' => 'nullable|in:1,0',
                'email' => 'nullable|email|max:191|unique:employees,email',
                'increment_month' => 'nullable|string',
                'uniform_allowance_eligibility' => 'required|in:1,0',
                'hra_eligibility' => 'required|in:1,0',
                'npa_eligibility' => 'required|in:1,0',
                'pancard' => [
                    'nullable',
                    'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                    'unique:employees,pancard',
                ],
                'status' => 'required|in:Active,Suspended,Resigned,Retired,On Leave',
                'status_effective_from' => 'nullable|date|after_or_equal:date_of_joining',
                'status_effective_till' => 'nullable|date|after:effective_from',
                'remarks' => 'nullable|string|max:255',
                'order_reference' => 'nullable|string|max:50',

                'designation' => 'nullable|string|min:2|max:191',
                'cadre' => 'nullable|string|min:2|max:191',
                'job_group' => 'nullable|in:A,B,C,D',
                'designation_effective_from' => 'nullable|date|after_or_equal:date_of_joining',
                'designation_effective_till' => 'nullable|date|after:designation_effective_from',
                'promotion_order_no' => 'nullable|string|max:50',

                'bank_name' => 'nullable|string|min:2|max:191',
                'branch_name' => 'nullable|string|min:2|max:191',
                'account_number' => 'nullable|string|max:30',
                'ifsc_code' => [
                    'nullable',
                    'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'
                ],
                'is_active' => 'nullable|in:1,0',
            ],
            [
                'pancard.regex' => 'The PAN card format is invalid. It should be like ABCDE1234F.'
            ]
        );

        $designation = null;
        $bank = null;

        $user = User::find($request['user_id']);
        if ($user->institute != $request['institute']) {
            return response()->json(['errorMsg' => 'User institute and enter institute not matched!'], 400);
        }

        DB::beginTransaction();

        $old_user_data = $user->toArray();

        $employee = new Employee();
        $employee->first_name = $request['first_name'];
        $employee->middle_name = $request['middle_name'];
        $employee->last_name = $request['last_name'];
        $employee->user_id = $request['user_id'];
        $employee->employee_code = $request['employee_code'];
        $employee->gender = $request['gender'];
        $employee->prefix = $request['prefix'];
        $employee->institute = $request['institute'];
        $employee->date_of_birth = $request['date_of_birth'];
        $employee->date_of_joining = $request['date_of_joining'];
        $employee->date_of_retirement = $request['date_of_retirement'];
        $employee->gis_eligibility = $request['gis_eligibility'];
        $employee->pwd_status = $request['pwd_status'];
        $employee->pension_scheme = $request['pension_scheme'];
        $employee->pension_number = $request['pension_number'];
        $employee->gis_no = $request['gis_no'];
        $employee->credit_society_member = $request['credit_society_member'];
        $employee->email = $request['email'];
        $employee->pancard = $request['pancard'];
        $employee->increment_month = $request['increment_month'];
        $employee->uniform_allowance_eligibility = $request['uniform_allowance_eligibility'];
        $employee->hra_eligibility = $request['hra_eligibility'];
        $employee->npa_eligibility = $request['npa_eligibility'];
        $employee->added_by = auth()->id();

        if ($request['status'] === 'Retired') {
            $user->is_retired = 1;
            $user->edited_by = auth()->id();
        }

        try {
            $user->save();
            $user->history()->create($old_user_data);

            $employee->save();

            $status = $employee->employeeStatus()->create([
                'status' => $request['status'],
                'effective_from' => $request['status_effective_from'],
                'effective_till' => $request['status_effective_till'],
                'remarks' => $request['remarks'],
                'order_reference' => $request['order_reference'],
                'added_by' => auth()->id()
            ]);
            if (
                $request->filled('designation') ||
                $request->filled('cadre') ||
                $request->filled('job_group') ||
                $request->filled('designation_effective_from')
            ) {
                $designation = $employee->employeeDesignation()->create([
                    'designation' => $request['designation'],
                    'cadre' => $request['cadre'],
                    'job_group' => $request['job_group'],
                    'effective_from' => $request['designation_effective_from'],
                    'effective_till' => $request['designation_effective_till'],
                    'remarks' => $request['remarks'],
                    'promotion_order_no' => $request['promotion_order_no'],
                    'added_by' => auth()->id()
                ]);
            }
            if (
                $request->filled('bank_name') ||
                $request->filled('branch_name') ||
                $request->filled('account_number') ||
                $request->filled('ifsc_code')
            ) {
                $bank = $employee->employeeBank()->create([
                    'bank_name' => $request['bank_name'],
                    'branch_name' => $request['branch_name'],
                    'account_number' => $request['account_number'],
                    'ifsc_code' => $request['ifsc_code'],
                    'is_active' => 1,
                    'added_by' => auth()->id()
                ]);
            }
            DB::commit();
            return response()->json(['successMsg' => 'Employee Created!', 'data' => [$employee, $status, $designation, $bank]]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $employee = Employee::find($id);
        if (!$employee) return response()->json(['errorMsg' => 'Employee Not Found!']);

        $request->validate(
            [
                'first_name' => 'required|string',
                'middle_name' => 'nullable|string',
                'last_name' => 'nullable|string',
                'user_id' => 'required|numeric|exists:users,id',
                'employee_code' => "required|string|min:4|max:20|unique:employees,employee_code,$id,id",
                'gender' => 'required|in:male,female,other',
                'prefix' => 'required|in:Mr.,Mrs.,Ms.,Dr.',
                'institute' => 'required|in:NIOH,ROHC,BOTH',
                'date_of_birth' => 'required|date',
                'date_of_joining' => 'required|date|after:date_of_birth',
                'date_of_retirement' => 'nullable|date|after:date_of_joining|after:date_of_birth',
                'gis_eligibility' => 'required|in:1,0',
                'pwd_status' => 'required|in:1,0',
                'pension_scheme' => 'required|in:GPF,NPS',
                'pension_number' => 'nullable|string|min:2|max:191',
                'gis_no' => 'nullable|string|min:2|max:191',
                'credit_society_member' => 'required|in:1,0',
                'email' => "required|email|max:191|unique:employees,email,$id,id",
                // 'pancard' => 'required|regex:/^([a-zA-Z]){5}([0-9]){4}([a-zA-Z]){1}?$/|unique:employees,pancard',
                'increment_month' => 'nullable|string',
                'uniform_allowance_eligibility' => 'required|in:1,0',
                'hra_eligibility' => 'required|in:1,0',
                'npa_eligibility' => 'required|in:1,0',
                'pancard' => [
                    'required',
                    'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                    "unique:employees,pancard,$id,id",
                ]
            ],
            [
                'pancard.regex' => 'The PAN card format is invalid. It should be like ABCDE1234F.'
            ]
        );

        DB::beginTransaction();

        // $employeeClone = $employee->replicate();
        // $employeeUpdatedBy = new EmployeeClone($employeeClone->toArray());
        // $employeeUpdatedBy->employee_id = $id;
        // $employeeUpdatedBy = $employeeClone->toArray();
        $old_data = $employee->toArray();
        // unset($old_data['id']);

        $employee->first_name = $request['first_name'];
        $employee->middle_name = $request['middle_name'];
        $employee->last_name = $request['last_name'];
        $employee->user_id = $request['user_id'];
        $employee->employee_code = $request['employee_code'];
        $employee->gender = $request['gender'];
        $employee->prefix = $request['prefix'];
        $employee->institute = $request['institute'];
        $employee->date_of_birth = $request['date_of_birth'];
        $employee->date_of_joining = $request['date_of_joining'];
        $employee->date_of_retirement = $request['date_of_retirement'];
        $employee->pwd_status = $request['pwd_status'];
        $employee->pension_scheme = $request['pension_scheme'];
        $employee->pension_number = $request['pension_number'];
        $employee->gis_eligibility = $request['gis_eligibility'];
        $employee->gis_no = $request['gis_no'];
        $employee->credit_society_member = $request['credit_society_member'];
        $employee->email = $request['email'];
        $employee->pancard = $request['pancard'];
        $employee->increment_month = $request['increment_month'];
        $employee->uniform_allowance_eligibility = $request['uniform_allowance_eligibility'];
        $employee->hra_eligibility = $request['hra_eligibility'];
        $employee->npa_eligibility = $request['npa_eligibility'];
        $employee->edited_by = auth()->id();

        try {
            $employee->save();
            $employee->history()->create($old_data);

            DB::commit();
            return response()->json([
                'successMsg' => 'Employee Updated!',
                'data' => $employee
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    // function destroy($id)
    // {
    //     $employee = Employee::find($id);
    //     if (!$employee) return response()->json(['errorMsg' => 'Employee Not Found!']);

    //     try {
    //         $employee->delete();
    //         return response()->json(['successMsg' => 'Employee Deleted!']);
    //     } catch (\Exception $e) {
    //         return response()->json(['errorMsg' => $e->getMessage()], 500);
    //     }
    // }

    function show($id)
    {
        $data = Employee::with([
            'user',
            'employeeStatus' => fn($q) => $q->orderBy('created_at', 'DESC'),
            'employeeBank' => fn($q) => $q->orderBy('created_at', 'DESC'),
            'employeeDesignation' => fn($q) => $q->orderBy('created_at', 'DESC'),
            'employeeLoan',
            'netSalary',
            'employeePayStructure',
            'employeeQuarter.quarter',
            'history.addedBy.roles:id,name',
            'history.editedBy.roles:id,name',
            'addedBy.roles:id,name',
            'editedBy.roles:id,name'
        ])->find($id);
        if (!$data) return response()->json(['errorMsg' => 'Employee not found!'], 404);

        // $employee_updated_history = Employee

        return response()->json(['data' => $data]);
    }
}
