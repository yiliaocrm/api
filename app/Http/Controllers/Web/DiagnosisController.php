<?php

namespace App\Http\Controllers\Web;

use App\Models\Diagnosis;
use App\Models\DiagnosisCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DiagnosisRequest;

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
            ->when(request('keyword'), function ($query) {
                $query->where('keyword', 'like', '%' . request('keyword') . '%');
            })
            ->orderBy($sort, $order)->paginate($rows);

        if ($query) {
            $data['rows']  = $query->items();
            $data['total'] = $query->total();
        } else {
            $data['rows']  = [];
            $data['total'] = 0;
        }
        return $data;
    }

    public function create(DiagnosisRequest $request)
    {
        return Diagnosis::create($request->formData());
    }

    public function update(DiagnosisRequest $request)
    {
        Diagnosis::find($request->id)->update($request->formData());
    }

    public function remove(DiagnosisRequest $request)
    {
        Diagnosis::find($request->id)->delete();
    }

    public function search()
    {

    }
}
