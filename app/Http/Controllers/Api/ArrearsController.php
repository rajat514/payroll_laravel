<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Arrears;
use Illuminate\Http\Request;

class ArrearsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Arrears::with('addedBy.role','editedBy.role')->get();
        return response()->json([
            'message' => 'Fetch arrear data successfull!',
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
            'pensioner_id' => 'required|exists:pensioner_information,id',
            'from_month' => 'required|date',
            'to_month' => 'required|date',
            'payment_month' => 'required|date',
            'basic_arrear' => 'required|numeric',
            'additional_arrear' => 'required|numeric',
            'dr_percentage' => 'required|numeric',
            'dr_arrear' => 'required|numeric',
            'total_arrear' => 'required|numeric',
            'remarks' => 'required|string|max:255',
        ]);


        $data = new Arrears();
        $data->pensioner_id = $request['pensioner_id'];
        $data->from_month = $request['from_month'];
        $data->to_month = $request['to_month'];
        $data->payment_month = $request['payment_month'];
        $data->basic_arrear = $request['basic_arrear'];
        $data->additional_arrear = $request['additional_arrear'];
        $data->dr_percentage = $request['dr_percentage'];
        $data->dr_arrear = $request['dr_arrear'];
        $data->total_arrear = $request['total_arrear'];
        $data->remarks = $request['remarks'];
        $data->added_by = auth()->id();

        try{
            $data->save();
            return response()->json([
                'message' => 'Arrear data create successfull!',
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
        $data = Arrears::find($id);
        if(!$data) return response()->json(['message' => 'Arrear not found!'],404);

        $request->validate([
            'pensioner_id' => 'required|exists:pensioner_information,id',
            'from_month' => 'required|date',
            'to_month' => 'required|date',
            'payment_month' => 'required|date',
            'basic_arrear' => 'required|numeric',
            'additional_arrear' => 'required|numeric',
            'dr_percentage' => 'required|numeric',
            'dr_arrear' => 'required|numeric',
            'total_arrear' => 'required|numeric',
            'remarks' => 'required|string|max:255',
        ]);

        $data->pensioner_id = $request['pensioner_id'];
        $data->from_month = $request['from_month'];
        $data->to_month = $request['to_month'];
        $data->payment_month = $request['payment_month'];
        $data->basic_arrear = $request['basic_arrear'];
        $data->additional_arrear = $request['additional_arrear'];
        $data->dr_percentage = $request['dr_percentage'];
        $data->dr_arrear = $request['dr_arrear'];
        $data->total_arrear = $request['total_arrear'];
        $data->remarks = $request['remarks'];
        $data->edited_by = auth()->id();

        try{
            $data->save();
            return response()->json([
                'message' => 'Arrear data update successfull!',
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
