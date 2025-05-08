<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DearnessRelief;
use Illuminate\Http\Request;

class DearnessReliefController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dr = DearnessRelief::all();
        return response()->json([
            'message' => 'Fetch dearness relief successfully',
            'data' => $dr 
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
            'effective_from' => 'required|date',
            'effective_to' => 'required|date',
            'dr_percentage' => 'required|numeric'
        ]);

        $data = new DearnessRelief();
        $data->effective_from = $request['effective_from'];
        $data->effective_to = $request['effective_to'];
        $data->dr_percentage = $request['dr_percentage'];

        try{
            $data->save();
            return response()->json([
                'message' => 'Dearness relief create successfully',
                'data' => $data 
            ],200);
        }catch(\Exception $e){
            return response()->json([
                'message' => $e->getMessage()
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
        $data = DearnessRelief::find($id);

        if(!$data) return response()->json(['message' => 'Dearness relief not found!'],404);

        $request->validate([
            'effective_from' => 'required|date',
            'effective_to' => 'required|date',
            'dr_percentage' => 'required|numeric'
        ]);

        $data->effective_from = $request['effective_from'];
        $data->effective_to = $request['effective_to'];
        $data->dr_percentage = $request['dr_percentage'];

        try{
            $data->save();
            return response()->json([
                'message' => 'Dearness relief update successfully',
                'data' => $data 
            ],200);
        }catch(\Exception $e){
            return response()->json([
                'message' => $e->getMessage()
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
