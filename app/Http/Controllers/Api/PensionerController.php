<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeStatus;
use App\Models\PensionerInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PensionerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = PensionerInformation::with('employee', 'addedBy.role', 'editedBy.role');

        $query->when(
            'retired_employee_id',
            fn($q) => $q->where('retired_employee_id', 'LIKE', '%' . request('retired_employee_id') . '%')
        );

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

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
        $request->validate([
            'ppo_no' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'type_of_pension' => 'required|in:Regular,Family',
            'retired_employee_id' => 'required',
            'relation' => 'required|in:Self,Spouse,Son,Daughter,Other',
            'dob' => 'required|date',
            'doj' => 'required|date',
            'dor' => 'required|date',
            'end_date' => 'nullable|date',
            'status' => 'required|in:Active,Expired,Suspended',
            'pan_number' => 'required|string|max:10',
            'pay_level' => 'required|string|max:50',
            'pay_commission' => 'required|string|max:50',
            'equivalent_level' => 'required|string|max:50',
            'address' => 'required|string',
            'city' => 'required|string|max:50',
            'state' => 'required|string|max:50',
            'pin_code' => 'required|string|max:10',
            'mobile_no' => 'required|string|max:15',
            'email' => 'required|email',
        ]);


        $employeeStatus = EmployeeStatus::where('employee_id', $request['retired_employee_id'])
            ->where('status', 'Retired')
            ->first();

        // Check if employee exists and is retired
        if (!$employeeStatus || $employeeStatus->status !== 'Retired') {
            return response()->json([
                'errorMsg' => 'The provided employee is either not found or not retired.',
            ], 400);
        }

        $pensioner = new PensionerInformation();
        $pensioner->ppo_no = $request['ppo_no'];
        $pensioner->name = $request['name'];
        $pensioner->type_of_pension = $request['type_of_pension'];
        $pensioner->retired_employee_id = $request['retired_employee_id'];
        $pensioner->relation = $request['relation'];
        $pensioner->dob = $request['dob'];
        $pensioner->doj = $request['doj'];
        $pensioner->dor = $request['dor'];
        $pensioner->end_date = $request['end_date'];
        $pensioner->status = $request['status'];
        $pensioner->pan_number = $request['pan_number'];
        $pensioner->pay_level = $request['pay_level'];
        $pensioner->pay_commission = $request['pay_commission'];
        $pensioner->equivalent_level = $request['equivalent_level'];
        $pensioner->address = $request['address'];
        $pensioner->city = $request['city'];
        $pensioner->state = $request['state'];
        $pensioner->pin_code = $request['pin_code'];
        $pensioner->mobile_no = $request['mobile_no'];
        $pensioner->email = $request['email'];
        $pensioner->added_by = auth()->id();

        try {
            $pensioner->save();
            return response()->json([
                'successMsg' => 'Pensioner detail create successfully!',
                'data' => $pensioner
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
        $pensioner = PensionerInformation::with('employee', 'addedBy', 'editedBy', 'history.addedBy', 'history.editedBy')->find($id);

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
        $pensioner = PensionerInformation::find($id);

        if (!$pensioner) return response()->json(['message' => 'Pensioner data not found!'], 404);

        $request->validate([
            'ppo_no' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'type_of_pension' => 'required|in:Regular,Family',
            'retired_employee_id' => 'required',
            'relation' => 'required|in:Self,Spouse,Son,Daughter,Other',
            'dob' => 'required|date',
            'doj' => 'required|date|after:dob',
            'dor' => 'required|date|after:dob|after:doj',
            'end_date' => 'nullable|date',
            'status' => 'required|in:Active,Expired,Suspended',
            'pan_number' => 'required|string|max:10',
            'pay_level' => 'required|string|max:50',
            'pay_commission' => 'required|string|max:50',
            'equivalent_level' => 'required|string|max:50',
            'address' => 'required|string',
            'city' => 'required|string|max:50',
            'state' => 'required|string|max:50',
            'pin_code' => 'required|string|max:10',
            'mobile_no' => 'required|string|max:15',
            'email' => 'required|email',
        ]);

        $employeeStatus = EmployeeStatus::where('employee_id', $request['retired_employee_id'])
            ->where('status', 'Retired')
            ->first();

        // Check if employee exists and is retired
        if (!$employeeStatus || $employeeStatus->status !== 'Retired') {
            return response()->json([
                'message' => 'The provided employee is either not found or not retired.',
            ], 400);
        }

        DB::beginTransaction();

        $old_data  = $pensioner->toArray();

        $pensioner->ppo_no = $request['ppo_no'];
        $pensioner->name = $request['name'];
        $pensioner->type_of_pension = $request['type_of_pension'];
        $pensioner->retired_employee_id = $request['retired_employee_id'];
        $pensioner->relation = $request['relation'];
        $pensioner->dob = $request['dob'];
        $pensioner->doj = $request['doj'];
        $pensioner->dor = $request['dor'];
        $pensioner->end_date = $request['end_date'];
        $pensioner->status = $request['status'];
        $pensioner->pan_number = $request['pan_number'];
        $pensioner->pay_level = $request['pay_level'];
        $pensioner->pay_commission = $request['pay_commission'];
        $pensioner->equivalent_level = $request['equivalent_level'];
        $pensioner->address = $request['address'];
        $pensioner->city = $request['city'];
        $pensioner->state = $request['state'];
        $pensioner->pin_code = $request['pin_code'];
        $pensioner->mobile_no = $request['mobile_no'];
        $pensioner->email = $request['email'];
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
        $pensioner = PensionerInformation::find($id);

        if (!$pensioner) return response()->json(['message' => 'Pensioner data not found!'], 404);

        $request->validate([
            'status' => 'required|in:Active,Expired,Suspended'
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
