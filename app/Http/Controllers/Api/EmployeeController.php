<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\EmployeeClone;
use App\Models\EmployeeStatus;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    function index()
    {
        $user = auth()->user();
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        // if (auth()->user()->isAdmin()) {
        // return response()->json(['data 1' => $user->institute]);
        // }

        $query = Employee::with('employeeStatus', 'user');

        $query->when(
            request('search'),
            fn($q) => $q->where('first_name', 'LIKE', '%' . request('search') . '%')
                ->orwhere('middle_name', 'LIKE', '%' . request('search') . '%')
                ->orwhere('last_name', 'LIKE', '%' . request('search') . '%')
        );

        // $query->where(
        //     'institute',
        //     $user->institute
        // );
        // $query->employeeStatus;

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

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        $request->validate(
            [
                'first_name' => 'required|string|min:2|max:191',
                'middle_name' => 'required|string|min:2|max:191',
                'last_name' => 'required|string|min:2|max:191',
                'user_id' => 'required|numeric|exists:users,id',
                'employee_code' => 'required|string|min:4|max:20|unique:employees,employee_code',
                'gender' => 'required|in:male,female,other',
                'institute' => 'required|in:NIOH,ROHC,BOTH',
                'date_of_birth' => 'required|date',
                'date_of_joining' => 'required|date|after:date_of_birth',
                'date_of_retirement' => 'nullable|date',
                'gis_eligibility' => 'required|in:1,0',
                'pwd_status' => 'required|in:1,0',
                'pension_scheme' => 'required|in:GPF,NPS',
                'pension_number' => 'nullable|string|min:2|max:191',
                'gis_no' => 'nullable|string|min:2|max:191',
                'credit_society_member' => 'required|in:1,0',
                'email' => 'required|email|max:191|unique:employees,email',
                'increment_month' => 'nullable|string',
                'uniform_allowance_eligibility' => 'required|in:1,0',
                'hra_eligibility' => 'required|in:1,0',
                'npa_eligibility' => 'required|in:1,0',
                'pancard' => [
                    'required',
                    'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                    'unique:employees,pancard',
                ],
                'status' => 'required|in:Active,Suspended,Resigned,Retired,On Leave',
                'effective_from' => 'required|date',
                'effective_till' => 'nullable|date|after:effective_from',
                'remarks' => 'nullable|string|max:255',
                'order_reference' => 'nullable|string|max:50',
            ],
            [
                'pancard.regex' => 'The PAN card format is invalid. It should be like ABCDE1234F.'
            ]
        );


        DB::beginTransaction();

        $employee = new Employee();
        $employee->first_name = $request['first_name'];
        $employee->middle_name = $request['middle_name'];
        $employee->last_name = $request['last_name'];
        $employee->user_id = $request['user_id'];
        $employee->employee_code = $request['employee_code'];
        $employee->gender = $request['gender'];
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


        try {

            $employee->save();
            $status = $employee->employeeStatus()->create([
                'status' => $request['status'],
                'effective_from' => $request['effective_from'],
                'effective_till' => $request['effective_till'],
                'remarks' => $request['remarks'],
                'order_reference' => $request['order_reference'],
                'added_by' => auth()->id()
            ]);
            DB::commit();
            return response()->json([
                'successMsg' => 'Employee Created!',
                'data' => [$employee, $status]
            ]);
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
                'first_name' => 'required|string|min:2|max:191',
                'middle_name' => 'required|string|min:2|max:191',
                'last_name' => 'required|string|min:2|max:191',
                'user_id' => 'required|numeric|exists:users,id',
                'employee_code' => "required|string|min:4|max:20|unique:employees,employee_code,$id,id",
                'gender' => 'required|in:male,female,other',
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
        $data = Employee::with(
            'user',
            'employeeStatus:id,employee_id,status,effective_from,effective_till',
            'employeeBank',
            'employeeDesignation',
            'netSalary',
            'employeePayStructure',
            'employeeQuarter',
            'addedBy',
            'editedBy',
            'history.addedBy',
            'history.editedBy',
        )->find($id);
        if (!$data) return response()->json(['errorMsg' => 'Employee not found!'], 404);

        // $employee_updated_history = Employee

        return response()->json(['data' => $data]);
    }
}
