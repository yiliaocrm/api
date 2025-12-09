<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PrescriptionFrequency;
use App\Http\Requests\Web\PrescriptionFrequencyRequest;

/**
 * 用药频次管理
 */
class PrescriptionFrequencyController extends Controller
{
    public function manage()
    {
        return PrescriptionFrequency::orderBy('id', 'desc')->get();
    }

    public function create(PrescriptionFrequencyRequest $request)
    {
        return PrescriptionFrequency::create([
            'name' => request('name')
        ]);
    }

    public function info()
    {
        return PrescriptionFrequency::find(request('id'));
    }

    public function update(PrescriptionFrequencyRequest $request)
    {
        PrescriptionFrequency::find(request('id'))->update([
            'name' => request('name')
        ]);
    }

    public function remove(PrescriptionFrequencyRequest $request)
    {
        PrescriptionFrequency::find(request('id'))->delete();
    }


}
