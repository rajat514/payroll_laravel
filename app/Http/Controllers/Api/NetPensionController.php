<?php

namespace App\Http\Controllers\Api;

use App\Helper\Mailer;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Employee;
use App\Models\NetPension;
use App\Models\PensionerInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NetPensionController extends Controller
{
    private \App\Models\User $user;

    private $all_permission_roles = ['IT Admin', 'Director', 'Pension Operator'];
    private $can_add_roles = ['IT Admin', 'Director', 'Pension Operator'];
    private $can_update_roles = ['IT Admin', 'Director', 'Pension Operator'];
    private $can_view_roles = ['IT Admin', 'Director', 'Pension Operator', 'Account Officer', 'Administrative Officer', 'End Users'];

    function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = \App\Models\User::find(auth()->id());
            return $next($request);
        });
    }

    function index()
    {
        // if (!$this->user->hasAnyRole($this->can_view_roles)) {
        //     return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        // }

        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        // $employee = Employee::where('user_id', $this->user->id)->first();
        // if ($employee) $pensioner = PensionerInformation::where('retired_employee_id', $employee->id)->first();

        $query = NetPension::with('pensionerDeduction', 'monthlyPension', 'pensionerRelation');

        // if ($this->user->hasAnyRole(['End Users'])) {
        //     $query->where('pensioner_id', $pensioner->id);
        // }

        // if ($this->user->hasRole(''))

        $query->when(
            request('month'),
            fn($q) => $q->where('month', request('month'))
        );

        $query->when(
            request('year'),
            fn($q) => $q->where('year', request('year'))
        );

        $query->when(
            request('ppo_no'),
            fn($q) => $q->whereHas(
                'pensionerRelation',
                fn($qe) => $qe->where('ppo_no', 'LIKE', '%' . request('ppo_no') . '%')
            )
        );

        $query->when(
            request('user_id'),
            fn($q) => $q->whereHas(
                'pensionerRelation',
                fn($qe) => $qe->where('user_id', request('user_id'))
            )
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

        if (!$this->user->hasRole('IT Admin')) {
            if ($this->user->hasAnyRole([
                'Pensioners Operator'
            ])) {
                // No restrictions – can view all salary entries
            } elseif ($this->user->hasAnyRole([
                'Drawing and Disbursing Officer (NIOH)'
            ])) {
                $query->where('pensioner_operator_status', 1);
            } elseif ($this->user->hasAnyRole(['Section Officer (Accounts)'])) {
                $query->where('ddo_status', 1);
            } elseif ($this->user->hasRole('Accounts Officer')) {
                $query->where('section_officer_status', 1);
            }
        }

        $total_count = $query->count();

        $data = $query->orderBy('year', 'DESC')->orderBy('month', 'DESC')->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function viewOwnPension()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $pensioner = PensionerInformation::where('user_id', $this->user->id)->first();

        if (!$pensioner) {
            return response()->json(['errorMsg', 'Pensioner not found!']);
        }

        $query = NetPension::with('pensionerDeduction', 'monthlyPension', 'pensionerRelation');

        $query->where('pensioner_id', $pensioner->id);

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
        // if (!$this->user->hasAnyRole($this->can_update_roles)) {
        //     return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        // }
        $netPension = NetPension::find($id);
        if (!$netPension) return response()->json(['errorMsg' => 'Net Pension not found!'], 404);

        $request->validate([
            'pensioner_id' => 'required|exists:pensioner_information,id',
            'pensioner_bank_id' => 'required|exists:bank_accounts,id',
            'month' => 'required|numeric|max:12|min:1',
            'year' => 'required|numeric|digits:4|min:1900',
            'processing_date' => 'required|date',
            'payment_date' => 'nullable|date|after:processing_date',
        ]);

        DB::beginTransaction();

        $old_data = $netPension->toArray();

        $netPension->pensioner_id = $request['pensioner_id'];
        $netPension->pensioner_bank_id = $request['pensioner_bank_id'];
        $netPension->month = $request['month'];
        $netPension->year = $request['year'];
        $netPension->processing_date = $request['processing_date'];
        $netPension->payment_date = $request['payment_date'];

        try {
            $netPension->save();

            $netPension->history()->create($old_data);

            DB::commit();
            return response()->json(['data' => $netPension]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function show($id)
    {
        // if (!$this->user->hasAnyRole($this->can_view_roles)) {
        //     return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        // }

        $data = NetPension::with(
            'history.addedBy.roles:id,name',
            'history.editedBy.roles:id,name',
            'addedBy.roles:id,name',
            'editedBy.roles:id,name',
            'pensionerDeduction',
            'monthlyPension',
            // 'pensioner.employee',
            // 'pensioner.pensionRelatedInfo',
            // 'pensionerBank',
            'history.monthlyPension',
            'history.pensionerDeduction'
        )->find($id);

        return response()->json(['data' => $data]);
    }

    // function verifyPension(Request $request)
    // {
    //     $request->validate([
    //         'pensioner_operator_status' => 'nullable|in:0,1',
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

    //     if ($user->hasRole('IT Admin')) {
    //         foreach ($data as $index) {
    //             $netPension = NetPension::find($index);
    //             // if (!$netPension) return response()->json(['errorMsg' => 'Net Pension not found!'], 404);

    //             if ($netPension->pensioner_operator_status == 0 && ($request['pensioner_operator_status'] ?? 0) == 1) {
    //                 $netPension->pensioner_operator_status = 1;
    //                 $netPension->pensioner_operator_date = $now;
    //             }

    //             // Step 2: DDO
    //             if (($request['ddo_status'] ?? 0) == 1) {
    //                 if ($netPension->pensioner_operator_status != 1) {
    //                     return response()->json(['errorMsg' => 'Pensioner Operator approval is required before DDO approval.'], 422);
    //                 }

    //                 if ($netPension->ddo_status == 0) {
    //                     $netPension->ddo_status = 1;
    //                     $netPension->ddo_date = $now;
    //                 }
    //             }

    //             // Step 3: Section Officer
    //             if (($request['section_officer_status'] ?? 0) == 1) {
    //                 if ($netPension->ddo_status != 1) {
    //                     return response()->json(['errorMsg' => 'DDO approval is required before Section Officer approval.'], 422);
    //                 }

    //                 if ($netPension->section_officer_status == 0) {
    //                     $netPension->section_officer_status = 1;
    //                     $netPension->section_officer_date = $now;
    //                 }
    //             }

    //             // Step 4: Account Officer
    //             if (($request['account_officer_status'] ?? 0) == 1) {
    //                 if ($netPension->section_officer_status != 1) {
    //                     return response()->json(['errorMsg' => 'Section Officer approval is required before Account Officer approval.'], 422);
    //                 }

    //                 if ($netPension->account_officer_status == 0) {
    //                     $netPension->account_officer_status = 1;
    //                     $netPension->account_officer_date = $now;
    //                 }
    //             }

    //             $netPension->edited_by = auth()->id();

    //             try {
    //                 $netPension->save();
    //                 return response()->json(['data' => 'Pension varified successfully!']);
    //             } catch (\Exception $e) {
    //                 return response()->json(['errorMsg' => $e->getMessage()], 500);
    //             }
    //         }
    //     } else if ($user->hasRole('Pensioners Operator')) {
    //         foreach ($data as $index) {
    //             $netPension = NetPension::find($index);
    //             // if (!$netPension) return response()->json(['errorMsg' => 'Net Pension not found!'], 404);

    //             $netPension->pensioner_operator_status = $request['pensioner_operator_status'] ?? 0;
    //             $netPension->pensioner_operator_date = $now;

    //             $netPension->edited_by = auth()->id();

    //             try {
    //                 $netPension->save();
    //                 return response()->json(['data' => 'Pension varified successfully!']);
    //             } catch (\Exception $e) {
    //                 return response()->json(['errorMsg' => $e->getMessage()], 500);
    //             }
    //         }
    //     } else if (
    //         $user->hasRole('Drawing and Disbursing Officer (NIOH)')
    //     ) {
    //         foreach ($data as $index) {
    //             $netPension = NetPension::find($index);
    //             // if (!$netPension) return response()->json(['errorMsg' => 'Net Pension not found!'], 404);

    //             $netPension->ddo_status = $request['ddo_status'] ?? 0;
    //             $netPension->ddo_date = $now;

    //             $netPension->edited_by = auth()->id();

    //             try {
    //                 $netPension->save();
    //                 return response()->json(['data' => 'Pension varified successfully!']);
    //             } catch (\Exception $e) {
    //                 return response()->json(['errorMsg' => $e->getMessage()], 500);
    //             }
    //         }
    //     } else if ($user->hasRole('Section Officer (Accounts)')) {
    //         foreach ($data as $index) {
    //             $netPension = NetPension::find($index);
    //             // if (!$netPension) return response()->json(['errorMsg' => 'Net Pension not found!'], 404);

    //             $netPension->section_officer_status = $request['section_officer_status'] ?? 0;
    //             $netPension->section_officer_date = $now;

    //             $netPension->edited_by = auth()->id();

    //             try {
    //                 $netPension->save();
    //                 return response()->json(['data' => 'Pension varified successfully!']);
    //             } catch (\Exception $e) {
    //                 return response()->json(['errorMsg' => $e->getMessage()], 500);
    //             }
    //         }
    //     } else if ($user->hasRole('Accounts Officer')) {
    //         foreach ($data as $index) {
    //             $netPension = NetPension::find($index);
    //             // if (!$netPension) return response()->json(['errorMsg' => 'Net Pension not found!'], 404);

    //             $netPension->account_officer_status = $request['account_officer_status'] ?? 0;
    //             $netPension->account_officer_date = $now;

    //             $netPension->edited_by = auth()->id();

    //             try {
    //                 $netPension->save();
    //                 return response()->json(['data' => 'Pension varified successfully!']);
    //             } catch (\Exception $e) {
    //                 return response()->json(['errorMsg' => $e->getMessage()], 500);
    //             }
    //         }
    //     } else {
    //         return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
    //     }
    // }

    public function verifyPension(Request $request)
    {
        $request->validate([
            'pensioner_operator_status' => 'nullable|in:0,1',
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
            $netSalary = NetPension::find($index);
            if (!$netSalary) {
                // Agar koi record na mile to agle par jayein
                continue;
            }

            // ✅ IT Admin ke liye logic waisa hi rahega, kyunki wo sabse powerful hai
            if ($user->hasRole('IT Admin')) {
                // Step 1: Salary Processing
                if ($netSalary->pensioner_operator_status == 0 && ($request->input('pensioner_operator_status') ?? 0) == 1) {
                    $netSalary->pensioner_operator_status = 1;
                    $netSalary->pensioner_operator_date = $now;
                }

                // Step 2: DDO
                if (($request->input('ddo_status') ?? 0) == 1) {
                    if ($netSalary->pensioner_operator_status != 1) {
                        return response()->json(['errorMsg' => 'Pensioner Operator approval is required before DDO approval.'], 422);
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

                $hasPermission = false;

                // Role: Salary Processing Coordinator
                if ($user->hasAnyRole(['Pensioners Operator'])) {
                    $hasPermission = true;
                    if ($netSalary->pensioner_operator_status == 0 && ($request->input('pensioner_operator_status') ?? 0) == 1) {
                        $netSalary->pensioner_operator_status = 1;
                        $netSalary->pensioner_operator_date = $now;
                    }
                }

                // Role: Drawing and Disbursing Officer
                if ($user->hasAnyRole(['Drawing and Disbursing Officer (NIOH)', 'Drawing and Disbursing Officer (ROHC)'])) {
                    $hasPermission = true;
                    if (($request->input('ddo_status') ?? 0) == 1) {
                        if ($netSalary->pensioner_operator_status != 1) {
                            return response()->json(['errorMsg' => 'Pensioner Operator approval is required before you can approve as DDO.'], 422);
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

    public function finalizePension(Request $request)
    {

        $user = Auth::user();

        if (!$user->hasAnyRole(['Accounts Officer', 'IT Admin'])) {
            return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        }

        $data = $request->input('selected_id');
        if (!is_array($data)) {
            $data = [$data];
        }

        $skipPension = [];
        $errors = [];
        $success = [];

        foreach ($data as $index) {
            $net_pension = NetPension::find($index);

            if (!$net_pension) {
                $skipPension[] = $index;
                continue;
            }

            if ($net_pension->account_officer_status != 1) {
                $errors[] = "{$net_pension->pensioner->name} Pension requires Account Officer approval.";
                continue;
            }

            if ($net_pension->is_finalize === 1) {
                $errors[] = " {$net_pension->pensioner->name} Pension already finalized.";
                continue;
            }

            try {
                $net_pension->is_finalize = 1;
                $net_pension->payment_date = now();
                $net_pension->finalized_date = now();
                $net_pension->edited_by = $user->id;
                $net_pension->save();

                $success[] = $net_pension->pensioner->name;
            } catch (\Exception $e) {
                $errors[] = "Error finalizing Pension {$index}: " . $e->getMessage();
            }
        }

        return response()->json([
            'successMsg' => count($success) > 0 ? 'Pension finalized successfully!' : null,
            'success' => $success,
            'skipped' => $skipPension,
            'errors' => $errors
        ]);
    }


    public function releasePension(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['Accounts Officer', 'IT Admin'])) {
            return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        }

        $data = $request->input('selected_id');
        if (!is_array($data)) {
            $data = [$data];
        }

        $skipPension = [];
        $errors = [];
        $success = [];

        foreach ($data as $index) {
            $net_pension = NetPension::with('pensionerRelation.user')->find($index);

            if (!$net_pension) {
                $skipPension[] = $index;
                continue;
            }

            if ($net_pension->is_finalize != 1) {
                $errors[] = " {$net_pension->pensioner->name} Pension requires finalize approval.";
                continue;
            }

            if ($net_pension->is_verified === 1) {
                $errors[] = " {$net_pension->pensioner->name} Pension already released.";
                continue;
            }

            try {
                $net_pension->is_verified = 1;
                $net_pension->released_date = now();
                $net_pension->edited_by = $user->id;
                $net_pension->save();

                $success[] = $net_pension->pensioner->name;

                if ($net_pension->pensioner->email) {
                    $sent = $this->sendSuccessMail(
                        $net_pension->pensioner->email,
                        $net_pension->month,
                        $net_pension->year,
                        $net_pension->id
                    );
                    if (!$sent) {
                        $errors[] = "Unable to send salary release email to {$net_pension->pensioner->name}. Please verify the email address.";
                        continue;
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Error releasing Pension {$index}: " . $e->getMessage();
            }
        }

        return response()->json([
            'successMsg' => count($success) > 0 ? 'Pension released successfully!' : null,
            'success'    => $success,
            'skipped'    => $skipPension,
            'errors'     => $errors
        ]);
    }

    private function sendSuccessMail($email, $month, $year, $netPensionId)
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
            $pension = NetPension::with([
                'pensionerDeduction',
                'monthlyPension',
            ])->find($netPensionId);

            if (!$pension) {
                return false;
            }

            // 2. Generate PDF using mPDF via service for better Indic support
            // $pdfService = new \App\Services\PdfService();
            // $pdfBinary = $pdfService->renderView('pdf.pensionPdf', compact('pension'));

            // // Save PDF temporarily in storage
            // $fileName = "pension-slip-{$pension->pensioner->ppo_no}-{$monthName}-{$year}.pdf";
            // $filePath = storage_path("app/public/{$fileName}");
            // file_put_contents($filePath, $pdfBinary);
            $pdfService = new \App\Services\PdfService();
            $pdfBinary = $pdfService->renderView('pdf.pensionPdf', compact('pension', 'monthName', 'year'));

            // Save PDF temporarily in storage
            $fileName = "pension-slip-{$pension->pensioner->ppo_no}-{$monthName}-{$year}.pdf";
            $filePath = storage_path("app/public/{$fileName}");
            file_put_contents($filePath, $pdfBinary);

            // $instituteName = $pension->pensioner->user->institute === 'ROHC'
            // ? 'ROHC - Regional Occupational Health Centre , Bangalore-562110'
            $instituteName = 'NIOH - National Institute of Occupational Health , Ahmedabad-380016';

            // 3. Prepare email body
            $body = "
        <html>
          <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
            <p>Dear {$pension->pensioner->name},</p>

            <p>
             Please find attached the pension slip for the month of {$monthName} {$year}.
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
                'subject' => "Pension Slip for $monthName $year",
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

    public function updateAllNetPensionsWithMissingData()
    {
        $netSalaries = NetPension::all();
        $updatedEmployees = [];
        foreach ($netSalaries as $netSalary) {
            $updated = false;

            // return response()->json(['data' => $netSalary]);
            // Agar employee field null hai
            if (empty($netSalary->pensioner) && $netSalary->pensioner_id) {
                $pensioner = PensionerInformation::with(
                    'employee'
                )
                    ->find($netSalary->pensioner_id);

                if ($pensioner) {
                    $netSalary->pensioner = $pensioner;
                    $updated = true;
                }
            }

            // Agar employee_bank field null hai
            if (empty($netSalary->pensioner_bank) && $netSalary->pensioner_bank_id) {
                $employeeBank = BankAccount::find($netSalary->pensioner_bank_id);

                if ($employeeBank) {
                    $netSalary->pensioner_bank = $employeeBank;
                    $updated = true;
                }
            }

            if ($updated) {
                $netSalary->save();

                // Employee ka naam ya code record kar lo
                $empName = $netSalary->pensioner_id
                    ? ($pensioner->name ?? "Employee ID: {$netSalary->pensioner_id}")
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
