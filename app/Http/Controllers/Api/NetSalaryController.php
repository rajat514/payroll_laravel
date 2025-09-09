<?php

namespace App\Http\Controllers\Api;

use App\Helper\Mailer;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\NetSalary;
use Barryvdh\DomPDF\Facade\Pdf;
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

    public function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = NetSalary::with('deduction', 'paySlip', 'employeeRelation');

        // 🔍 Institute filtering based on role
        $query->when(true, function ($q) {
            // IT Admin sees everything, so we apply no institute filter for them.
            if ($this->user->hasRole('IT Admin')) {
                return; // Exit the closure, applying no filter.
            }

            $allowedInstitutes = [];

            // Check for NIOH-related roles and add their institutes to the list.
            if ($this->user->hasAnyRole([
                'Salary Processing Coordinator (NIOH)',
                'Drawing and Disbursing Officer (NIOH)'
            ])) {
                $allowedInstitutes = array_merge($allowedInstitutes, ['NIOH', 'BOTH']);
            }

            // Independently, check for ROHC-related roles and add their institute.
            if ($this->user->hasAnyRole([
                'Salary Processing Coordinator (ROHC)',
                'Drawing and Disbursing Officer (ROHC)'
            ])) {
                $allowedInstitutes[] = 'ROHC';
            }

            // If the user's roles resulted in any specific institute permissions, apply them.
            if (!empty($allowedInstitutes)) {
                $q->whereHas(
                    'employeeRelation',
                    fn($qn) => $qn->whereIn('institute', array_unique($allowedInstitutes))
                );
            } else {
                // This block is for users without the special Coordinator/DDO roles (e.g., 'End Users').
                // We fall back to filtering by their own assigned institute.
                if ($this->user->institute !== 'BOTH') {
                    $q->whereHas(
                        'employeeRelation',
                        fn($qn) => $qn->where('institute', $this->user->institute)
                    );
                }
                // If a user's institute is 'BOTH' and they don't have special roles, they see all institutes.
            }
        });

        // 🔍 Additional filters
        $query->when(
            request('employee_id'),
            fn($q) =>
            $q->where('employee_id', request('employee_id'))
        );

        $query->when(
            request('month'),
            fn($q) =>
            $q->where('month', request('month'))
        );

        $query->when(
            request('year'),
            fn($q) =>
            $q->where('year', request('year'))
        );

        $query->when(
            request('is_verified') !== null,
            fn($q) =>
            $q->where('is_verified', request('is_verified'))
        );

        $query->when(
            request('is_finalize') !== null,
            fn($q) =>
            $q->where('is_finalize', request('is_finalize'))
        );


        $institute = request('institute');

        if ($institute) {
            $query->whereHas('employeeRelation', function ($qn) use ($institute) {
                if ($institute === 'NIOH') {
                    $qn->whereIn('institute', ['NIOH', 'BOTH']);
                } elseif ($institute === 'ROHC') {
                    $qn->where('institute', 'ROHC');
                }
            });
        }


        // 👥 Role-based approval level filters
        if (!$this->user->hasRole('IT Admin')) {
            if ($this->user->hasAnyRole([
                'Salary Processing Coordinator (NIOH)',
                'Salary Processing Coordinator (ROHC)'
            ])) {
                // No restrictions – can view all salary entries
            } elseif ($this->user->hasAnyRole([
                'Drawing and Disbursing Officer (NIOH)',
                'Drawing and Disbursing Officer (ROHC)'
            ])) {
                $query->where('salary_processing_status', 1);
            } elseif ($this->user->hasAnyRole(['Section Officer (Accounts)'])) {
                $query->where('ddo_status', 1);
            } elseif ($this->user->hasRole('Accounts Officer')) {
                $query->where('section_officer_status', 1);
            }
        }

        // 📊 Final result
        $total_count = $query->count();

        $data = $query
            ->orderBy('year', 'DESC')
            ->orderBy('month', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $data,
            'total_count' => $total_count
        ]);
    }


    function viewOwnSalary()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $employee = Employee::where('user_id', $this->user->id)->first();

        if (!$employee) {
            return response()->json(['errorMsg', 'Employee not found!']);
        }

        $query = NetSalary::with('deduction', 'paySlip');

        $query->where('employee_id', $employee->id);

        $query->when(
            request('month'),
            fn($q) => $q->where('month', request('month'))
        );

        $query->when(
            request('year'),
            fn($q) => $q->where('year', request('year'))
        );

        $query->where('is_verified', 1);

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
            'employeeRelation.employeeDesignation',
            'employeeRelation.employeeStatus',
            'employeeBankRelation',
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

    public function finalizeSalary(Request $request)
    {

        $user = Auth::user();

        if (!$user->hasAnyRole(['Accounts Officer', 'IT Admin'])) {
            return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        }

        $data = $request->input('selected_id');
        if (!is_array($data)) {
            $data = [$data];
        }

        $skipSalary = [];
        $errors = [];
        $success = [];

        foreach ($data as $index) {
            $net_salary = NetSalary::find($index);

            if (!$net_salary) {
                $skipSalary[] = $index;
                continue;
            }

            if ($net_salary->account_officer_status != 1) {
                $errors[] = "{$net_salary->employee->name} Salary requires Account Officer approval.";
                continue;
            }

            if ($net_salary->is_finalize === 1) {
                $errors[] = " {$net_salary->employee->name} Salary already finalized.";
                continue;
            }

            try {
                $net_salary->is_finalize = 1;
                $net_salary->finalized_date = now();
                $net_salary->payment_date = now();
                $net_salary->edited_by = $user->id;
                $net_salary->save();

                $success[] = $net_salary->employee->name;
            } catch (\Exception $e) {
                $errors[] = "Error finalizing Salary {$index}: " . $e->getMessage();
            }
        }

        return response()->json([
            'successMsg' => count($success) > 0 ? 'Salary finalized successfully!' : null,
            'success' => $success,
            'skipped' => $skipSalary,
            'errors' => $errors
        ]);
    }


    public function releaseSalary(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['Accounts Officer', 'IT Admin'])) {
            return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        }

        $data = $request->input('selected_id');
        if (!is_array($data)) {
            $data = [$data];
        }

        $skipSalary = [];
        $errors = [];
        $success = [];

        foreach ($data as $index) {
            $net_salary = NetSalary::find($index);

            if (!$net_salary) {
                $skipSalary[] = $index;
                continue;
            }

            if ($net_salary->is_finalize != 1) {
                $errors[] = " {$net_salary->employee->name} Salary requires finalize approval.";
                continue;
            }

            if ($net_salary->is_verified === 1) {
                $errors[] = " {$net_salary->employee->name} Salary already released.";
                continue;
            }

            try {
                $net_salary->is_verified = 1;
                $net_salary->released_date = now();
                $net_salary->edited_by = $user->id;
                $net_salary->save();


                // $sent = $this->sendSuccessMail($net_salary->employee->email, $net_salary->month, $net_salary->year);
                $sent = $this->sendSuccessMail(
                    $net_salary->employee->email,
                    $net_salary->month,
                    $net_salary->year,
                    $net_salary->id
                );
                if (!$sent) {
                    $errors[] = "Unable to send salary release email to {$net_salary->employee->name}. Please verify the email address.";
                    continue;
                }
                $success[] = $net_salary->employee->name;
            } catch (\Exception $e) {
                $errors[] = "Error releasing Salary {$index}: " . $e->getMessage();
            }
        }

        return response()->json([
            'successMsg' => count($success) > 0 ? 'Salary released successfully!' : null,
            'success'    => $success,
            'skipped'    => $skipSalary,
            'errors'     => $errors
        ]);
    }

    private function sendSuccessMail($email, $month, $year, $netSalaryId)
    {
        try {
            $months = [
                1 => 'January',
                2 => 'February',
                3 => 'March',
                4 => 'April',
                5 => 'May',
                6 => 'June',
                7 => 'July',
                8 => 'August',
                9 => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December',
            ];

            $monthName = $months[$month] ?? $month;

            // 1. Get salary record with relations
            $salary = NetSalary::with([
                'deduction.deductionRecoveries',
                'paySlip.salaryArrears',
                'employeeRelation'
            ])->find($netSalaryId);

            if (!$salary) {
                return false;
            }

            // 2. Generate PDF using mPDF via service for better Indic support
            $pdfService = new \App\Services\PdfService();
            $pdfBinary = $pdfService->renderView('pdf.pdf', compact('salary', 'monthName', 'year'));

            // Save PDF temporarily in storage
            $fileName = "salary-slip-{$salary->employee->employee_code}-{$monthName}-{$year}.pdf";
            $filePath = storage_path("app/public/{$fileName}");
            file_put_contents($filePath, $pdfBinary);

            $instituteName = $salary->employee->institute === 'ROHC'
                ? 'ROHC - Regional Occupational Health Centre , Bangalore-562110'
                : 'NIOH - National Institute of Occupational Health , Ahmedabad-380016';

            // 3. Prepare email body
            $body = "
        <html>
          <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
            <p>Dear {$salary->employee->name},</p>

            <p>
             Please find attached the salary slip for the month of {$monthName} {$year}.
            </p>

            <p>
              <strong>Note: </strong> This is a system-generated email. Kindly do not replyto this message.
            </p>

            <p>
              Regards,<br/>
              <strong>For Account Staff</strong>
               {$instituteName}
            </p>
          </body>
        </html>
        ";

            // 4. Prepare mail data with attachment
            $mailer = new Mailer();
            $mailData = [
                'to' => $email,
                'subject' => "Salary Slip for $monthName $year",
                'body' => $body,
                'attachments' => [$filePath] // 👈 pass attachment
            ];

            $result = $mailer->sendMail($mailData);

            // Remove temp file after sending
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            return $result;
        } catch (\Exception $e) {
            \Log::error('Salary Mail failed: ' . $e->getMessage());
            return false;
        }
    }



    public function updateAllNetSalariesWithMissingData()
    {
        $netSalaries = NetSalary::all();
        $updatedEmployees = [];
        foreach ($netSalaries as $netSalary) {
            $updated = false;

            // return response()->json(['data' => $netSalary]);
            // Agar employee field null hai
            if (empty($netSalary->employee) && $netSalary->employee_id) {
                $employee = Employee::with(
                    'employeeDesignation',
                    'employeeStatus',
                    'employeePayStructure.PayMatrixCell.payMatrixLevel',
                    'employeeBank',
                    'latestEmployeeDesignation'
                )
                    ->find($netSalary->employee_id);

                if ($employee) {
                    $netSalary->employee = $employee;
                    $updated = true;
                }
            }

            // Agar employee_bank field null hai
            if (empty($netSalary->employee_bank) && $netSalary->employee_bank_id) {
                $employeeBank = EmployeeBankAccount::find($netSalary->employee_bank_id);

                if ($employeeBank) {
                    $netSalary->employee_bank = $employeeBank;
                    $updated = true;
                }
            }

            if ($updated) {
                $netSalary->save();

                // Employee ka naam ya code record kar lo
                $empName = $netSalary->employee_id
                    ? ($employee->name ?? "Employee ID: {$netSalary->employee_id}")
                    : "Unknown Employee";
                $updatedEmployees[] = $empName;
            }
        }

        return response()->json([
            "message" => "All NetSalary records checked and updated where missing.",
            "updated_employees" => $updatedEmployees
        ]);
    }
}
