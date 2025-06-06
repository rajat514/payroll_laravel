<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NetPension;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NetPensionController extends Controller
{
    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = NetPension::with('pensionerDeduction', 'monthlyPension');

        $query->when(
            request('month'),
            fn($q) => $q->where('month', 'LIKE', '%' . request('month') . '%')
        );

        $query->when(
            request('year'),
            fn($q) => $q->where('year', 'LIKE', '%' . request('year') . '%')
        );

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function update(Request $request, $id)
    {
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
        $data = NetPension::with('addedBy', 'editedBy', 'history.addedBy', 'history.editedBy', 'pensionerDeduction', 'monthlyPension')->find($id);

        return response()->json(['data' => $data]);
    }
}
