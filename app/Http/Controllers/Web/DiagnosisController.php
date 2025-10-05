<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Diagnosis\CreateRequest;
use App\Http\Requests\Diagnosis\RemoveRequest;
use App\Http\Requests\Diagnosis\UpdateRequest;
use App\Models\Diagnosis;
use App\Models\DiagnosisCategory;

class DiagnosisController extends Controller
{
    public function manage()
    {
        $data        = [];
        $sort        = request('sort', 'id');
        $rows        = request('rows', 10);
        $order       = request('order', 'desc');
        $category_id = request('category_id');
        $query       = Diagnosis::whereIn('category_id', DiagnosisCategory::find($category_id)->getAllChild()->pluck('id'))
        ->when(request('keyword'), function($query) {
            $query->where('keyword', 'like', '%' . request('keyword') . '%');
        })
        ->orderBy($sort, $order)->paginate($rows);

        if($query) {
            $data['rows']  = $query->items();
            $data['total'] = $query->total();
        } else {
            $data['rows']  = [];
            $data['total'] = 0;
        }
        return $data;
    }

    public function create(CreateRequest $request)
    {
    	return Diagnosis::create($request->only('category_id', 'name', 'code'));
    }

    public function update(UpdateRequest $request)
    {
    	Diagnosis::find($request->id)->update($request->only('category_id', 'name', 'code'));
    }

    public function remove(RemoveRequest $request)
    {
    	Diagnosis::find($request->id)->delete();
    }

    public function search()
    {

    }
}
