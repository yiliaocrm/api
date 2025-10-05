<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrescriptionWays\CreateRequest;
use App\Http\Requests\PrescriptionWays\RemoveRequest;
use App\Http\Requests\PrescriptionWays\UpdateRequest;
use App\Models\PrescriptionWays;

/**
 * 用药途径管理
 */
class PrescriptionWaysController extends Controller
{
    public function manage()
    {
        return PrescriptionWays::orderBy('id', 'desc')->get();
    }

    public function create(CreateRequest $request)
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

    public function update(UpdateRequest $request)
    {
        PrescriptionWays::find(request('id'))->update([
            'name' => request('name'),
            'type' => request('type'),
        ]);
    }

    public function remove(RemoveRequest $request)
    {
        PrescriptionWays::find(request('id'))->delete();
    }


}
