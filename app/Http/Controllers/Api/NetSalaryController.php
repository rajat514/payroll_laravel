<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\NetSalary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NetSalaryController extends Controller
{
    private \App\Models\User $user;

    private $all_permission_roles = ['IT Admin', 'Director', 'Pension Operator'];
    private $can_add_roles = ['IT Admin', 'Director', 'Pension Operator'];
    private $can_update_roles = ['IT Admin', 'Director', 'Pension Operator'];
    private $can_view_roles = ['IT Admin', 'Director', 'Pension Operator', ' Administrative Officer'];

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

        $query = NetSalary::with('deduction', 'paySlip', 'employee');

        $query->when(
            $this->user->institute !== 'BOTH',
            fn($q) => $q->whereHas(
                'employee',
                fn($qn) => $qn->where('institute', $this->user->institute)
            )
        );

        $query->when(
            request('employee_id'),
            fn($q) => $q->where('employee_id', request('employee_id'))
        );

        $query->when(
            request('month'),
            fn($q) => $q->where('month', request('month'))
        );

        $query->when(
            request('year'),
            fn($q) => $q->where('year', request('year'))
        );

        $query->when(
            request('is_verified') != null,
            fn($q) => $q->where('is_verified', request('is_verified'))
        );

        $total_count = $query->count();

        $data = $query->orderBy('year', 'DESC')->orderBy('month', 'DESC')->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function viewOwnSalary()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $employee = Employee::where('user_id', $this->user->id)->first();

        $query = NetSalary::with('deduction', 'paySlip', 'employee');

        $query->where('employee_id', $employee->id);

        $query->when(
            request('month'),
            fn($q) => $q->where('month', request('month'))
        );

        $query->when(
            request('year'),
            fn($q) => $q->where('year', request('year'))
        );

        $query->when(
            request('is_verified') != null,
            fn($q) => $q->where('is_verified', request('is_verified'))
        );

        $total_count = $query->count();

        $data = $query->orderBy('year', 'DESC')->orderBy('month', 'DESC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function update(Request $request, $id)
    {
        $netSalary = NetSalary::find($id);
        if (!$netSalary) return response()->json(['errorMsg' => 'Net Salary not found!'], 404);

        $request->validate([
            'employee_id' => 'required|numeric|exists:employees,id',
            'month' => 'required|numeric|max:12|min:1',
            'year' => 'required|numeric|digits:4|min:1900',
            'processing_date' => 'required|date',
            'payment_date' => 'nullable|date|after:processing_date',
            'employee_bank_id' => 'required|numeric|exists:employee_bank_accounts,id',
        ]);


        $employeeBank = EmployeeBankAccount::find($request['employee_bank_id']);
        if (!$employeeBank->is_active) return response()->json(['errorMsg' => 'Please fill the correct Bank'], 400);
        if ($employeeBank->employee_id != $request['employee_id']) return response()->json(['errorMsg' => 'Employee Bank not found!'], 404);

        DB::beginTransaction();

        $old_data = $netSalary->toArray();

        $netSalary->employee_id = $request['employee_id'];
        $netSalary->month = $request['month'];
        $netSalary->year = $request['year'];
        $netSalary->processing_date = $request['processing_date'];
        $netSalary->payment_date = $request['payment_date'];
        // $netSalary->net_amount = $request['net_amount'];
        $netSalary->employee_bank_id = $request['employee_bank_id'];
        $netSalary->edited_by = auth()->id();

        try {
            $netSalary->save();

            $netSalary->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Net Salary Updated!', 'data' => $netSalary]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function show($id)
    {
        $netSalary = NetSalary::with(
            'employee.employeeDesignation',
            'employeeBank',
            'deduction.deductionRecoveries',
            'paySlip.salaryArrears',
            'verifiedBy.roles:id,name',
            'history.verifiedBy.roles:id,name',
            'history.addedBy.roles:id,name',
            'history.editedBy.roles:id,name',
            'addedBy.roles:id,name',
            'editedBy.roles:id,name',
            'history.employee',
            // 'history.paySlip',
            // 'history.deduction.deductionRecoveries',
            // 'history.paySlip.salaryArrears'
        )->find($id);

        return response()->json(['data' => $netSalary]);
    }

    // function verifySalary(Request $request)
    // {
    //     $request->validate([
    //         'salary_processing_status' => 'nullable|in:0,1',
    //         'ddo_status' => 'nullable|in:0,1',
    //         'section_officer_status' => 'nullable|in:0,1',
    //         'account_officer_status' => 'nullable|in:0,1',
    //     ]);

    //     $data = $request['selected_id'];
    //     if (!is_array($data)) {
    //         $data = [$data];
    //     }



    //     $user = auth()->user();
    //     $roles = $user->getRoleNames(); // Collection of role names
    //     $now = now();
    //     // dd($now);

    //     // ✅ If user is IT Admin, approve all roles
    //     foreach ($data as $index) {
    //         if ($user->hasRole('IT Admin')) {
    //             // foreach ($data as $index) {
    //             $netSalary = NetSalary::find($index);
    //             if (!$netSalary) return response()->json(['errorMsg' => 'Net Salary not found!'], 404);

    //             $now = now();

    //             // Step 1: Salary Processing
    //             if ($netSalary->salary_processing_status == 0 && ($request['salary_processing_status'] ?? 0) == 1) {
    //                 $netSalary->salary_processing_status = 1;
    //                 $netSalary->salary_processing_date = $now;
    //             }

    //             // Step 2: DDO
    //             if (($request['ddo_status'] ?? 0) == 1) {
    //                 if ($netSalary->salary_processing_status != 1) {
    //                     return response()->json(['errorMsg' => 'Salary Processing approval is required before DDO approval.'], 422);
    //                 }

    //                 if ($netSalary->ddo_status == 0) {
    //                     $netSalary->ddo_status = 1;
    //                     $netSalary->ddo_date = $now;
    //                 }
    //             }

    //             // Step 3: Section Officer
    //             if (($request['section_officer_status'] ?? 0) == 1) {
    //                 if ($netSalary->ddo_status != 1) {
    //                     return response()->json(['errorMsg' => 'DDO approval is required before Section Officer approval.'], 422);
    //                 }

    //                 if ($netSalary->section_officer_status == 0) {
    //                     $netSalary->section_officer_status = 1;
    //                     $netSalary->section_officer_date = $now;
    //                 }
    //             }

    //             // Step 4: Account Officer
    //             if (($request['account_officer_status'] ?? 0) == 1) {
    //                 if ($netSalary->section_officer_status != 1) {
    //                     return response()->json(['errorMsg' => 'Section Officer approval is required before Account Officer approval.'], 422);
    //                 }

    //                 if ($netSalary->account_officer_status == 0) {
    //                     $netSalary->account_officer_status = 1;
    //                     $netSalary->account_officer_date = $now;
    //                 }
    //             }


    //             $netSalary->edited_by = auth()->id();

    //             try {
    //                 $netSalary->save();
    //                 // return response()->json(['data' => 'Salary varified successfully!']);
    //             } catch (\Exception $e) {
    //                 return response()->json(['errorMsg' => $e->getMessage()], 500);
    //             }
    //             // }
    //         } else if (
    //             $user->hasRole('Salary Processing Coordinator (NIOH)') ||
    //             $user->hasRole('Salary Processing Coordinator (ROHC)')
    //         ) {
    //             // foreach ($data as $index) {
    //             $netSalary = NetSalary::find($index);
    //             if (!$netSalary) return response()->json(['errorMsg' => 'Net Salary not found!'], 404);

    //             $netSalary->salary_processing_status = $request['salary_processing_status'] ?? 0;
    //             $netSalary->salary_processing_date = $now;

    //             $netSalary->edited_by = auth()->id();

    //             try {
    //                 $netSalary->save();
    //                 // return response()->json(['data' => 'Salary varified successfully!']);
    //             } catch (\Exception $e) {
    //                 return response()->json(['errorMsg' => $e->getMessage()], 500);
    //             }
    //             // }
    //         } else if (
    //             $user->hasRole('Drawing and Disbursing Officer (NIOH)') ||
    //             $user->hasRole('Drawing and Disbursing Officer (ROHC)')
    //         ) {
    //             // foreach ($data as $index) {
    //             $netSalary = NetSalary::find($index);
    //             if (!$netSalary) return response()->json(['errorMsg' => 'Net Salary not found!'], 404);

    //             $netSalary->ddo_status = $request['ddo_status'] ?? 0;
    //             $netSalary->ddo_date = $now;

    //             $netSalary->edited_by = auth()->id();

    //             try {
    //                 $netSalary->save();
    //                 // return response()->json(['data' => 'Salary varified successfully!']);
    //             } catch (\Exception $e) {
    //                 return response()->json(['errorMsg' => $e->getMessage()], 500);
    //             }
    //             // }
    //         } else if ($user->hasRole('Section Officer (Accounts)')) {
    //             // foreach ($data as $index) {
    //             $netSalary = NetSalary::find($index);
    //             if (!$netSalary) return response()->json(['errorMsg' => 'Net Salary not found!'], 404);

    //             $netSalary->section_officer_status = $request['section_officer_status'] ?? 0;
    //             $netSalary->section_officer_date = $now;

    //             $netSalary->edited_by = auth()->id();

    //             try {
    //                 $netSalary->save();
    //                 // return response()->json(['data' => 'Salary varified successfully!']);
    //             } catch (\Exception $e) {
    //                 return response()->json(['errorMsg' => $e->getMessage()], 500);
    //             }
    //             // }
    //         } else if ($user->hasRole('Accounts Officer')) {
    //             // foreach ($data as $index) {
    //             $netSalary = NetSalary::find($index);
    //             if (!$netSalary) return response()->json(['errorMsg' => 'Net Salary not found!'], 404);

    //             $netSalary->account_officer_status = $request['account_officer_status'] ?? 0;
    //             $netSalary->account_officer_date = $now;

    //             $netSalary->edited_by = auth()->id();

    //             try {
    //                 $netSalary->save();
    //                 // return response()->json(['data' => 'Salary varified successfully!']);
    //             } catch (\Exception $e) {
    //                 return response()->json(['errorMsg' => $e->getMessage()], 500);
    //             }
    //             // }
    //         } else {
    //             return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
    //         }
    //     }
    //     return response()->json(['data' => 'Salary varified successfully!']);
    // }

    public function verifySalary(Request $request)
    {
        $request->validate([
            'salary_processing_status' => 'nullable|in:0,1',
            'ddo_status' => 'nullable|in:0,1',
            'section_officer_status' => 'nullable|in:0,1',
            'account_officer_status' => 'nullable|in:0,1',
            'selected_id' => 'required',
        ]);

        $data = $request->input('selected_id');
        if (!is_array($data)) {
            $data = [$data];
        }

        $user = Auth::user();
        $now = now();

        // Loop through each selected salary record
        foreach ($data as $index) {
            $netSalary = NetSalary::find($index);
            if (!$netSalary) {
                // Agar koi record na mile to agle par jayein
                continue;
            }

            // ✅ IT Admin ke liye logic waisa hi rahega, kyunki wo sabse powerful hai
            if ($user->hasRole('IT Admin')) {
                // Step 1: Salary Processing
                if ($netSalary->salary_processing_status == 0 && ($request->input('salary_processing_status') ?? 0) == 1) {
                    $netSalary->salary_processing_status = 1;
                    $netSalary->salary_processing_date = $now;
                }

                // Step 2: DDO
                if (($request->input('ddo_status') ?? 0) == 1) {
                    if ($netSalary->salary_processing_status != 1) {
                        return response()->json(['errorMsg' => 'Salary Processing approval is required before DDO approval.', $request['salary_processing_status']], 422);
                    }
                    if ($netSalary->ddo_status == 0) {
                        $netSalary->ddo_status = 1;
                        $netSalary->ddo_date = $now;
                    }
                }

                // Step 3: Section Officer
                if (($request->input('section_officer_status') ?? 0) == 1) {
                    if ($netSalary->ddo_status != 1) {
                        return response()->json(['errorMsg' => 'DDO approval is required before Section Officer approval.'], 422);
                    }
                    if ($netSalary->section_officer_status == 0) {
                        $netSalary->section_officer_status = 1;
                        $netSalary->section_officer_date = $now;
                    }
                }

                // Step 4: Account Officer
                if (($request->input('account_officer_status') ?? 0) == 1) {
                    if ($netSalary->section_officer_status != 1) {
                        return response()->json(['errorMsg' => 'Section Officer approval is required before Account Officer approval.'], 422);
                    }
                    if ($netSalary->account_officer_status == 0) {
                        $netSalary->account_officer_status = 1;
                        $netSalary->account_officer_date = $now;
                        $netSalary->is_verified = 1;
                        $netSalary->payment_date = $now;
                    }
                }
            } else {
                // ✅ Non-Admin users ke liye naya logic
                // Yahan hum `else if` ke bajaye multiple `if` ka istemal karenge
                // taki ek user multiple roles ke task perform kar sake.

                $hasPermission = false;

                // Role: Salary Processing Coordinator
                if ($user->hasAnyRole(['Salary Processing Coordinator (NIOH)', 'Salary Processing Coordinator (ROHC)'])) {
                    $hasPermission = true;
                    if ($netSalary->salary_processing_status == 0 && ($request->input('salary_processing_status') ?? 0) == 1) {
                        $netSalary->salary_processing_status = 1;
                        $netSalary->salary_processing_date = $now;
                    }
                }

                // Role: Drawing and Disbursing Officer
                if ($user->hasAnyRole(['Drawing and Disbursing Officer (NIOH)', 'Drawing and Disbursing Officer (ROHC)'])) {
                    $hasPermission = true;
                    if (($request->input('ddo_status') ?? 0) == 1) {
                        if ($netSalary->salary_processing_status != 1) {
                            return response()->json(['errorMsg' => 'Salary Processing approval is required before you can approve as DDO.'], 422);
                        }
                        if ($netSalary->ddo_status == 0) {
                            $netSalary->ddo_status = 1;
                            $netSalary->ddo_date = $now;
                        }
                    }
                }

                // Role: Section Officer (Accounts)
                if ($user->hasRole('Section Officer (Accounts)')) {
                    $hasPermission = true;
                    if (($request->input('section_officer_status') ?? 0) == 1) {
                        if ($netSalary->ddo_status != 1) {
                            return response()->json(['errorMsg' => 'DDO approval is required before you can approve as Section Officer.'], 422);
                        }
                        if ($netSalary->section_officer_status == 0) {
                            $netSalary->section_officer_status = 1;
                            $netSalary->section_officer_date = $now;
                        }
                    }
                }

                // Role: Accounts Officer
                if ($user->hasRole('Accounts Officer')) {
                    $hasPermission = true;
                    if (($request->input('account_officer_status') ?? 0) == 1) {
                        if ($netSalary->section_officer_status != 1) {
                            return response()->json(['errorMsg' => 'Section Officer approval is required before you can approve as Account Officer.'], 422);
                        }
                        if ($netSalary->account_officer_status == 0) {
                            $netSalary->account_officer_status = 1;
                            $netSalary->account_officer_date = $now;
                            $netSalary->is_verified = 1;
                            $netSalary->payment_date = $now;
                        }
                    }
                }

                // Agar user ke paas inme se koi bhi role nahi hai to access deny karein
                if (!$hasPermission) {
                    return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
                }
            }

            // Agar model mein koi badlav hua hai to hi save karein
            if ($netSalary->isDirty()) {
                $netSalary->edited_by = $user->id;
                try {
                    $netSalary->save();
                } catch (\Exception $e) {
                    return response()->json(['errorMsg' => $e->getMessage()], 500);
                }
            }
        }

        return response()->json(['data' => 'Salary verified successfully!']);
    }
}
