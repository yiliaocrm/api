<?php

namespace App\Http\Controllers\Web;

use App\Models\PrescriptionWays;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\PrescriptionWaysRequest;

/**
 * 用药途径管理
 */
class PrescriptionWaysController extends Controller
{
    public function manage()
    {
        return PrescriptionWays::orderBy('id', 'desc')->get();
    }

    public function create(PrescriptionWaysRequest $request)
    {
        return PrescriptionWays::create([
            'name' => request('name'),
            'type' => request('type'),
        ]);
    }

    public function info()
    {
        return PrescriptionWays::find(request('id'));
    }

    public function update(PrescriptionWaysRequest $request)
    {
        PrescriptionWays::find(request('id'))->update([
            'name' => request('name'),
            'type' => request('type'),
        ]);
    }

    public function remove(PrescriptionWaysRequest $request)
    {
        PrescriptionWays::find(request('id'))->delete();
    }


}
