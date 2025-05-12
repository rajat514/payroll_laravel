<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PensionerDocuments;
use Illuminate\Http\Request;

class PensionDocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = PensionerDocuments::with('pensioner','addedBy.role','editedBy.role')->get();
        return response()->json([
            'message' => 'Fetch pension document data successfully!',
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
        $fileName = '';
        $request->validate([
            'pensioner_id' => 'required|exists:pensioner_information,id',
            'document_type' => 'required|in:PAN Card,Address Proof,Bank Details,Retirement Order,Life Certificate',
            'document_number' => 'required|max:50',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date',
            'file' => 'required|mimes:pdf|max:2048'
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/pension'), $fileName);
        }

        $data = new PensionerDocuments();
        $data->pensioner_id = $request['pensioner_id'];
        $data->document_type = $request['document_type'];
        $data->document_number = $request['document_number'];
        $data->issue_date = $request['issue_date'];
        $data->expiry_date = $request['expiry_date'];
        $data->file_path = 'uploads/pension/' . $fileName;
        $data->upload_date = now();
        $data->added_by = auth()->id();

        try{
            $data->save();
            return response()->json(['message' => 'Document uploaded successfully', 'data' => $data], 201);
        }catch(\Exception $e){
            return response()->json(['message' => $e->getMessage()], 500);
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
        $data = PensionerDocuments::find($id);
        if(!$data) return response()->json([
            'message' => 'Pensioner Document not found!'
        ],404);

        $request->validate([
            'pensioner_id' => 'required|exists:pensioner_information,id',
            'document_type' => 'required|in:PAN Card,Address Proof,Bank Details,Retirement Order,Life Certificate',
            'document_number' => 'required|max:50',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date',
            'file' => 'required|mimes:pdf|max:2048'
        ]);
        
        // Delete previous file if exists
        if ($data->file_path && file_exists(public_path($data->file_path))) {
            unlink(public_path($data->file_path));
        }

        $fileName = '';
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/pension'), $fileName);
        }

        $data->pensioner_id = $request['pensioner_id'];
        $data->document_type = $request['document_type'];
        $data->document_number = $request['document_number'];
        $data->issue_date = $request['issue_date'];
        $data->expiry_date = $request['expiry_date'];
        $data->file_path = 'uploads/pension/' . $fileName;
        $data->upload_date = now();
        $data->edited_by = auth()->id();

        try{
            $data->save();
            return response()->json(['message' => 'Document updated successfully', 'data' => $data], 201);
        }catch(\Exception $e){
            return response()->json(['message' => $e->getMessage()], 500);
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
