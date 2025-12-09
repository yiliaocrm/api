<?php

namespace App\Http\Controllers\Web;

use App\Models\PrescriptionUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\PrescriptionUnitRequest;

/**
 * 用量单位
 */
class PrescriptionUnitController extends Controller
{
    public function manage()
    {
        return PrescriptionUnit::orderBy('id', 'desc')->get();
    }

    public function create(PrescriptionUnitRequest $request)
    {
        return PrescriptionUnit::create([
            'name' => request('name')
        ]);
    }

    public function info()
    {
        return PrescriptionUnit::find(request('id'));
    }

    public function update(PrescriptionUnitRequest $request)
    {
        PrescriptionUnit::find(request('id'))->update([
            'name' => request('name')
        ]);
    }

    public function remove(PrescriptionUnitRequest $request)
    {
        PrescriptionUnit::find(request('id'))->delete();
    }


}
