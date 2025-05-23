<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PensionDeduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PensionDeductionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = PensionDeduction::with('monthlyPension', 'addedBy.role', 'editedBy.role');

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
            'pension_id' => 'required|exists:monthly_pensions,id',
            'deduction_type' => 'required|in:Income Tax,Recovery,Other',
            'amount' => 'required|numeric',
            'description' => 'string'
        ]);

        $data = new PensionDeduction();
        $data->pension_id = $request['pension_id'];
        $data->deduction_type = $request['deduction_type'];
        $data->amount = $request['amount'];
        $data->description = $request['description'];
        $data->added_by = auth()->id();

        try {
            $data->save();
            return response()->json([
                'successMsg' => 'Pension Deduction create successfully!',
                'data' => $data
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
        $data = PensionDeduction::with('addedBy', 'editedBy', 'history.addedBy', 'history.editedBy',)->find($id);

        return response()->json(['data' => $data]);
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
        $data = PensionDeduction::find($id);
        if (!$data) return response()->json(['errorMsg' => 'Pension deduction not found!'], 404);

        $request->validate([
            'pension_id' => 'required|exists:monthly_pensions,id',
            'deduction_type' => 'required|in:Income Tax,Recovery,Other',
            'amount' => 'required|numeric',
            'description' => 'string'
        ]);

        DB::beginTransaction();

        $old_data = $data->toArray();

        $data->pension_id = $request['pension_id'];
        $data->deduction_type = $request['deduction_type'];
        $data->amount = $request['amount'];
        $data->description = $request['description'];
        $data->edited_by = auth()->id();

        try {
            $data->save();

            $data->history()->create($old_data);

            DB::commit();
            return response()->json([
                'successMsg' => 'Pension Deduction update successfully!',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'errorMsg' => $e->getMessage(),
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
}
