<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\DiagnosisCategory\CreateRequest;
use App\Http\Requests\DiagnosisCategory\RemoveRequest;
use App\Http\Requests\DiagnosisCategory\UpdateRequest;
use App\Models\DiagnosisCategory;

class DiagnosisCategoryController extends Controller
{
    public function all()
    {
		$data = DiagnosisCategory::select('id', 'name AS text', 'parentid', 'child')->get()->toArray();
		return list_to_tree($data);
    }

    public function create(CreateRequest $request)
    {
    	return DiagnosisCategory::create([
    		'name'     => $request->name,
    		'parentid' => $request->parentid,
    	]);
    }

    public function update(UpdateRequest $request)
    {
    	$category = DiagnosisCategory::find($request->id);

    	$category->name = $request->name;
    	$category->save();

    	return $category;
    }

    public function remove(RemoveRequest $request)
    {
        DiagnosisCategory::find($request->id)->delete();
    }
}
