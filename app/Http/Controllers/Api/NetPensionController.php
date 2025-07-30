<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        $employee = Employee::where('user_id', $this->user->id)->first();
        if ($employee) $pensioner = PensionerInformation::where('retired_employee_id', $employee->id)->first();
        $query = NetPension::with('pensionerDeduction', 'monthlyPension', 'pensioner.employee');

        if ($this->user->hasAnyRole(['End Users'])) {
            $query->where('pensioner_id', $pensioner->id);
        }

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
            fn($q) => $q->where('is_verified', 'LIKE', '%' . request('is_verified') . '%')
        );

        $total_count = $query->count();

        $data = $query->orderBy('year', 'DESC')->orderBy('month', 'DESC')->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function update(Request $request, $id)
    {
        if (!$this->user->hasAnyRole($this->can_update_roles)) {
            return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        }
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
            'pensioner.employee',
            'pensioner.pensionRelatedInfo',
            'pensionerBank',
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
                        return response()->json(['errorMsg' => 'Salary Processing approval is required before DDO approval.'], 422);
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
                        $netSalary->payment_date = $now;
                    }
                }
            } else {
                // ✅ Non-Admin users ke liye naya logic
                // Yahan hum `else if` ke bajaye multiple `if` ka istemal karenge
                // taki ek user multiple roles ke task perform kar sake.

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
