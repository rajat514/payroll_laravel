<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = BankAccount::with('pensioner', 'addedBy.role', 'editedBy.role');

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
            'pensioner_id' => 'required|exists:pensioner_information,id',
            'bank_name' => 'required|string|max:100',
            'branch_name' => 'required|string|max:100',
            'account_no' => 'required|string|unique:bank_accounts,account_no',
            'ifsc_code' => 'required|string|max:20',
        ], [
            'pensioner_id.required' => 'Please select a pensioner.',
            'pensioner_id.exists' => 'Selected pensioner does not exist.',
            'bank_name.required' => 'Bank name is required.',
            'bank_name.string' => 'Bank name must be a valid string.',
            'branch_name.required' => 'Branch name is required.',
            'branch_name.string' => 'Branch name must be a valid string.',
            'account_no.required' => 'Account number is required.',
            'account_no.string' => 'Account number must be a valid string.',
            'account_no.unique' => 'This account number already exists.',
            'ifsc_code.required' => 'IFSC code is required.',
            'ifsc_code.string' => 'IFSC code must be a valid string.',
            'ifsc_code.max' => 'IFSC code should not be longer than 20 characters.',
        ]);

        $bank = new BankAccount();
        $bank->pensioner_id = $request['pensioner_id'];
        $bank->bank_name = $request['bank_name'];
        $bank->branch_name = $request['branch_name'];
        $bank->account_no = $request['account_no'];
        $bank->ifsc_code = $request['ifsc_code'];
        $bank->is_active = $request['is_active'];
        $bank->added_by = auth()->id();


        try {
            $bank->save();
            return response()->json([
                'successMsg' => 'Bank account detail create successfully!',
                'data' => $bank
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
        $bank = BankAccount::find($id);

        if (!$bank) return response()->json([
            'errorMsg' => 'Bank account not found!'
        ], 404);

        $bank->is_active === 0 ? $bank->is_active = 1 : $bank->is_active = 0;
        $bank->edited_by = auth()->id();

        try {
            $bank->update();
            return response()->json([
                'data' => $bank
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'errorMsg' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $bank = BankAccount::find($id);

        if (!$bank) return response()->json([
            'errorMsg' => 'Bank account not found!'
        ], 404);

        $request->validate([
            'pensioner_id' => 'required|exists:pensioner_information,id',
            'bank_name' => 'required|string|max:100',
            'branch_name' => 'required|string|max:100',
            'account_no' => 'required|string',
            'ifsc_code' => 'required|string|max:20',
        ], [
            'pensioner_id.required' => 'Please select a pensioner.',
            'pensioner_id.exists' => 'Selected pensioner does not exist.',
            'bank_name.required' => 'Bank name is required.',
            'bank_name.string' => 'Bank name must be a valid string.',
            'branch_name.required' => 'Branch name is required.',
            'branch_name.string' => 'Branch name must be a valid string.',
            'account_no.required' => 'Account number is required.',
            'account_no.string' => 'Account number must be a valid string.',
            'ifsc_code.required' => 'IFSC code is required.',
            'ifsc_code.string' => 'IFSC code must be a valid string.',
            'ifsc_code.max' => 'IFSC code should not be longer than 20 characters.',
        ]);

        $bank->pensioner_id = $request['pensioner_id'];
        $bank->bank_name = $request['bank_name'];
        $bank->branch_name = $request['branch_name'];
        $bank->account_no = $request['account_no'];
        $bank->ifsc_code = $request['ifsc_code'];
        $bank->is_active = $request['is_active'];
        $bank->edited_by = auth()->id();



        try {
            $bank->update();
            return response()->json([
                'successMsg' => 'Bank account detail update successfully!',
                'data' => $bank
            ], 200);
        } catch (\Exception $e) {
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
