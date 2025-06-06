<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DesignationController extends Controller
{
    function index()
    {
        $data = Designation::all();
        return response()->json(['data' => $data]);
    }

    function show($id)
    {
        $data = Designation::with('addedBy', 'editedBy', 'history.addedBy', 'history.editedBy')->find($id);

        return response()->json(['data' => $data]);
    }

    function store(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string|max:191',
            'options' => 'nullable|array',
            'options.*' => 'string|max:100'
        ]);

        $fields['added_by'] = auth()->id();

        try {
            $designation = Designation::create($fields);
            return response()->json(['successMsg' => "Designation created!", 'data' => $designation]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        $designation = Designation::find($id);
        if (!$designation) return response()->json(['errorMsg' => 'Designation not found!'], 404);

        $request->validate([
            'name' => 'required|string|max:191',
            'options' => 'nullable|array',
            'options.*' => 'string|max:100'
        ]);

        DB::beginTransaction();

        $old_data = $designation->toArray();

        $designation->name = $request['name'];
        $designation->options = $request['options'];
        $designation->edited_by = auth()->id();

        try {
            $designation->save();

            $designation->history()->create($old_data);
            DB::commit();
            // $designation = Designation::create($fields);
            return response()->json(['successMsg' => "Designation updated!", 'data' => $designation]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }
}
