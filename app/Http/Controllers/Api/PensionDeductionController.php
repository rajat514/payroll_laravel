<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PensionDeduction;
use Illuminate\Http\Request;

class PensionDeductionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = PensionDeduction::with('pension')->get();
        return response()->json([
            'message' => 'Fetch deduction data successfull!',
            'data' => $data
        ],200);
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
            'pension_id' => 'required|exists:pensioner_information,id',
            'deduction_type' => 'required|in:Income Tax,Recovery,Other',
            'amount' => 'required|numeric',
            'description' => 'string'
        ]);

        $data = new PensionDeduction();
        $data->pension_id = $request['pension_id'];
        $data->deduction_type = $request['deduction_type'];
        $data->amount = $request['amount'];
        $data->description = $request['description'];

        try{
            $data->save();
            return response()->json([
                'message' => 'Pension Deduction create successfully!',
                'data' => $data
            ],200);
        }catch(\Exception $e){
            return response()->json([
                'message' => $e->getMessage(),
            ],500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        if(!$data) return response()->json(['message' => 'Pension deduction not found!'],404);

        $request->validate([
            'pension_id' => 'required|exists:pensioner_information,id',
            'deduction_type' => 'required|in:Income Tax,Recovery,Other',
            'amount' => 'required|numeric',
            'description' => 'string'
        ]);

        $data->pension_id = $request['pension_id'];
        $data->deduction_type = $request['deduction_type'];
        $data->amount = $request['amount'];
        $data->description = $request['description'];

        try{
            $data->save();
            return response()->json([
                'message' => 'Pension Deduction update successfully!',
                'data' => $data
            ],200);
        }catch(\Exception $e){
            return response()->json([
                'message' => $e->getMessage(),
            ],500);
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
