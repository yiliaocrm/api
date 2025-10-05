<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrescriptionFrequency\CreateRequest;
use App\Http\Requests\PrescriptionFrequency\RemoveRequest;
use App\Http\Requests\PrescriptionFrequency\UpdateRequest;
use App\Models\PrescriptionFrequency;

/**
 * 用药频次管理
 */
class PrescriptionFrequencyController extends Controller
{
    public function manage()
    {
        return PrescriptionFrequency::orderBy('id', 'desc')->get();
    }

    public function create(CreateRequest $request)
    {
        return PrescriptionFrequency::create([
            'name' => request('name')
        ]);
    }

    public function info()
    {
        return PrescriptionFrequency::find(request('id'));
    }

    public function update(UpdateRequest $request)
    {
        PrescriptionFrequency::find(request('id'))->update([
            'name' => request('name')
        ]);
    }

    public function remove(RemoveRequest $request)
    {
        PrescriptionFrequency::find(request('id'))->delete();
    }


}
