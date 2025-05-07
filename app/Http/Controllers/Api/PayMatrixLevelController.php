<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayMatrixLevel;
use Illuminate\Http\Request;

class PayMatrixLevelController extends Controller
{
    function index()
    {
        $data = PayMatrixLevel::all();

        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3|max:30',
            'description' => 'required|string|min:3|max:191'
        ]);

        $payMatrixLevel = new PayMatrixLevel();
        $payMatrixLevel->name = $request['name'];
        $payMatrixLevel->description = $request['description'];

        try {
            $payMatrixLevel->save();

            return response()->json(['successMsg' => 'Pay Matrix Level Created!', 'data' => $payMatrixLevel]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $payMatrixLevel = PayMatrixLevel::find($id);
        if (!$payMatrixLevel) return response()->json(['errorMsg' => 'Pay Matrix Level not found!']);

        $request->validate([
            'name' => 'required|string|min:3|max:30',
            'description' => 'required|string|min:3|max:191'
        ]);

        $payMatrixLevel->name = $request['name'];
        $payMatrixLevel->description = $request['description'];

        try {
            $payMatrixLevel->save();

            return response()->json(['successMsg' => 'Pay Matrix Level Updated!', 'data' => $payMatrixLevel]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
