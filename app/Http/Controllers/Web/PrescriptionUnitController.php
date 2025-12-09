<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrescriptionUnit\CreateRequest;
use App\Http\Requests\PrescriptionUnit\RemoveRequest;
use App\Http\Requests\PrescriptionUnit\UpdateRequest;
use App\Models\PrescriptionUnit;
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

    public function create(CreateRequest $request)
    {
        return PrescriptionUnit::create([
            'name' => request('name')
        ]);
    }

    public function info()
    {
        return PrescriptionUnit::find(request('id'));
    }

    public function update(UpdateRequest $request)
    {
        PrescriptionUnit::find(request('id'))->update([
            'name' => request('name')
        ]);
    }

    public function remove(RemoveRequest $request)
    {
        PrescriptionUnit::find(request('id'))->delete();
    }


}
