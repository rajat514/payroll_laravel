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
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = PensionerDocuments::with('pensioner', 'addedBy.role', 'editedBy.role');

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
        $fileName = '';
        $request->validate([
            'pensioner_id' => 'required|exists:pensioner_information,id',
            'document_type' => 'required|in:PAN Card,Address Proof,Bank Details,Retirement Order,Life Certificate',
            'document_number' => 'required|max:50',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
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

        try {
            $data->save();
            return response()->json(['successMsg' => 'Document uploaded successfully', 'data' => $data], 201);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
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
        if (!$data) return response()->json([
            'errorMsg' => 'Pensioner Document not found!'
        ], 404);

        $request->validate([
            'pensioner_id' => 'required|exists:pensioner_information,id',
            'document_type' => 'required|in:PAN Card,Address Proof,Bank Details,Retirement Order,Life Certificate',
            'document_number' => 'required|max:50',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
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

        try {
            $data->save();
            return response()->json(['successMsg' => 'Document updated successfully', 'data' => $data], 201);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
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
