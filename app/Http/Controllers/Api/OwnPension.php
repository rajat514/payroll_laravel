<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NetPension;
use Illuminate\Http\Request;

class OwnPension extends Controller
{
    function ownPension()
    {


        $query = NetPension::with('pensionerDeduction', 'monthlyPension', 'pensioner.employee');

        $query->where('month', request('month'));

        $query->where('year', request('year'));

        $query->whereHas(
            'pensioner',
            fn($qe) => $qe->where('ppo_no', request('ppo_no'))
        );

        $query->where('is_verified', 1);

        $total_count = $query->count();

        $data = $query->get()->first();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function viewPension($id)
    {

        $data = NetPension::with(
            'history.addedBy.roles:id,name',
            'history.editedBy.roles:id,name',
            'addedBy.roles:id,name',
            'editedBy.roles:id,name',
            'pensionerDeduction',
            'monthlyPension',
            'pensioner.employee',
            'pensioner.pensionRelatedInfo',
            'pensionerBank',
            'history.monthlyPension',
            'history.pensionerDeduction'
        )->find($id);

        return response()->json(['data' => $data]);
    }
}
