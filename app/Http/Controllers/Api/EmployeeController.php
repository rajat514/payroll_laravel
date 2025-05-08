<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\EmployeeStatus;

class EmployeeController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $data = Employee::with('addby:id,name,role_id', 'editby:id,name,role_id');

        $data->when(
            request('search'),
            fn($q) => $q->where('first_name', 'LIKE', '%' . request('search') . '%')
                ->orwhere('last_name', 'LIKE', '%' . request('search') . '%')
        );

        $data->when(
            request('current_status'),
            fn($q) => $q->whereHas(
                'employeeStatus',
                fn($qn) => $qn->where('status', request('current_status'))
                    ->whereDate('effective_from', '<=', date('Y-m-d'))
                    ->whereDate('effective_till', '>=', date('Y-m-d'))
            )
        );

        $total_count = $data->count();

        $data = $data->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        $request->validate(
            [
                'first_name' => 'required|string|min:2|max:191',
                'last_name' => 'required|string|min:2|max:191',
                'gender' => 'required|in:male,female,other',
                'date_of_birth' => 'required|date',
                'date_of_joining' => 'required|date',
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
                'effective_till' => 'nullable|date',
                'remarks' => 'nullable|string|max:255',
                'order_reference' => 'nullable|string|max:50',
            ],
            [
                'pancard.regex' => 'The PAN card format is invalid. It should be like ABCDE1234F.'
            ]
        );

        $employee = new Employee();
        $employee->first_name = $request['first_name'];
        $employee->last_name = $request['last_name'];
        $employee->gender = $request['gender'];
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
            return response()->json([
                'successMsg' => 'Employee Created!',
                'data' => [$employee, $status]
            ]);
        } catch (\Exception $e) {
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
                'last_name' => 'required|string|min:2|max:191',
                'gender' => 'required|in:male,female,other',
                'date_of_birth' => 'required|date',
                'date_of_joining' => 'required|date',
                'date_of_retirement' => 'nullable|date',
                'gis_eligibility' => 'required|in:1,0',
                'pwd_status' => 'required|in:1,0',
                'pension_scheme' => 'required|in:GPF,NPS',
                'pension_number' => 'nullable|string|min:2|max:191',
                'gis_no' => 'nullable|string|min:2|max:191',
                'credit_society_member' => 'required|in:1,0',
                'email' => 'required|email|max:191|unique:employees,email',
                // 'pancard' => 'required|regex:/^([a-zA-Z]){5}([0-9]){4}([a-zA-Z]){1}?$/|unique:employees,pancard',
                'increment_month' => 'nullable|string',
                'uniform_allowance_eligibility' => 'required|in:1,0',
                'hra_eligibility' => 'required|in:1,0',
                'npa_eligibility' => 'required|in:1,0',
                'pancard' => [
                    'required',
                    'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'
                ]
            ],
            [
                'pancard.regex' => 'The PAN card format is invalid. It should be like ABCDE1234F.'
            ]
        );


        $employee->first_name = $request['first_name'];
        $employee->last_name = $request['last_name'];
        $employee->gender = $request['gender'];
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
            return response()->json([
                'successMsg' => 'Employee Updated!',
                'data' => $employee
            ]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function destroy($id)
    {
        $employee = Employee::find($id);
        if (!$employee) return response()->json(['errorMsg' => 'Employee Not Found!']);

        try {
            $employee->delete();
            return response()->json(['successMsg' => 'Employee Deleted!']);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function show($id)
    {
        $data = Employee::with(
            'employeeStatus:id,employee_id,status,effective_from,effective_till',
            'employeeBank',
            'employeeDesignation',
            'addby:id,name,role_id',
            'editby:id,name,role_id'
        )->find($id);
        if (!$data) return response()->json(['errorMsg' => 'Employee not found!'], 404);

        return response()->json(['data' => $data]);
    }
}
