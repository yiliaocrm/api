<?php

namespace App\Http\Controllers\Web;

use App\Models\DiagnosisCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DiagnosisCategoryRequest;

class DiagnosisCategoryController extends Controller
{
    public function all()
    {
        $data = DiagnosisCategory::select('id', 'name AS text', 'parentid', 'child')->get()->toArray();
        return list_to_tree($data);
    }

    public function create(DiagnosisCategoryRequest $request)
    {
        return DiagnosisCategory::create([
            'name'     => $request->name,
            'parentid' => $request->parentid,
        ]);
    }

    public function update(DiagnosisCategoryRequest $request)
    {
        $category = DiagnosisCategory::find($request->id);

        $category->name = $request->name;
        $category->save();

        return $category;
    }

    public function remove(DiagnosisCategoryRequest $request)
    {
        DiagnosisCategory::find($request->id)->delete();
    }
}
