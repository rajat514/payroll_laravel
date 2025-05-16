<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quarter;

class QuarterController extends Controller
{

    function index()
    {
        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = Quarter::with('addby:id,name,role_id', 'editby:id,name,role_id');

        $total_count = $query->count();

        $data = $query->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function store(Request $request)
    {
        $request->validate([
            'quarter_no' => 'required|string|max:191',
            'type' => 'required|in:B,C',
            'license_fee' => 'required|string|max:191'
        ]);

        $quarter = new Quarter();
        $quarter->quarter_no = $request['quarter_no'];
        $quarter->type = $request['type'];
        $quarter->license_fee = $request['license_fee'];
        $quarter->added_by = auth()->id();

        try {
            $quarter->save();
            return response()->json([
                'successMsg' => 'Quarter Created!',
                'data' => $quarter
            ]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $quarter = Quarter::find($id);
        if (!$quarter) return response()->json(['errorMsg' => 'Quarter Not Found!'], 404);

        $request->validate([
            'quarter_no' => 'required|string|max:191',
            'type' => 'required|in:B,C',
            'license_fee' => 'required|string|max:191'
        ]);

        $quarter->quarter_no = $request['quarter_no'];
        $quarter->type = $request['type'];
        $quarter->license_fee = $request['license_fee'];
        $quarter->edited_by = auth()->id();

        try {
            $quarter->save();
            return response()->json([
                'successMsg' => 'Quarter Updated!',
                'data' => $quarter
            ]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function destroy($id)
    {
        $quarter = Quarter::find($id);
        if (!$quarter) return response()->json(['errorMsg' => 'Quarter Not Found!'], 404);

        try {
            $quarter->delete();
            return response()->json(['successMsg' => 'Quarter Deleted!']);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
