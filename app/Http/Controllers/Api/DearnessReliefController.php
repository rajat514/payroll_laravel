<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DearnessRelief;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DearnessReliefController extends Controller
{
    private \App\Models\User $user;

    private $all_permission_roles = ['IT Admin', 'Director'];

    function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = \App\Models\User::find(auth()->id());
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = DearnessRelief::query();

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

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
        // Check if user has required roles
        if (!$this->user->hasAnyRole($this->all_permission_roles)) {
            return response()->json(['errorMsg' => 'Access Denied! Only IT Admin and Director can perform this action.'], 403);
        }

        $request->validate([
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'dr_percentage' => 'required|numeric'
        ]);

        $isSmallDate = DearnessRelief::where('effective_from', '>=', $request['effective_from'])->get()->first();
        if ($isSmallDate) return response()->json(['errorMsg' => 'Effective From date is smaller than previous!'], 400);

        $dearness = DearnessRelief::get()->last();

        DB::beginTransaction();

        if ($dearness) {
            $dearness->effective_to = \Carbon\Carbon::parse($request['effective_from'])->subDay()->format('Y-m-d');

            try {
                $dearness->save();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['errorMsg' => $e->getMessage()], 500);
            }
        }

        $data = new DearnessRelief();
        $data->effective_from = $request['effective_from'];
        $data->effective_to = $request['effective_to'];
        $data->dr_percentage = $request['dr_percentage'];
        $data->added_by = auth()->id();

        try {
            $data->save();
            DB::commit();
            return response()->json(['successMsg' => 'Dearness relief create successfully', 'data' => $data], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $data = DearnessRelief::with('history.addedBy.roles:id,name', 'history.editedBy.roles:id,name', 'addedBy.roles:id,name', 'editedBy.roles:id,name')->find($id);

        return response()->json(['data' => $data]);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Check if user has required roles
        if (!$this->user->hasAnyRole($this->all_permission_roles)) {
            return response()->json(['errorMsg' => 'Access Denied! Only IT Admin and Director can perform this action.'], 403);
        }

        $data = DearnessRelief::find($id);

        if (!$data) return response()->json(['errorMsg' => 'Dearness relief not found!'], 404);

        $request->validate([
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'dr_percentage' => 'required|numeric'
        ]);

        DB::beginTransaction();

        $old_data = $data->toArray();

        $data->effective_from = $request['effective_from'];
        $data->effective_to = $request['effective_to'];
        $data->dr_percentage = $request['dr_percentage'];
        $data->edited_by = auth()->id();

        try {
            $data->save();

            $data->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'Dearness relief update successfully', 'data' => $data], 200);
        } catch (\Exception $e) {
            DB::rollBack();
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
