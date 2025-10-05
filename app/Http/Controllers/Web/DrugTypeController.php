<?php

namespace App\Http\Controllers\Web;

use App\Models\GoodsType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DrugTypeRequest;

class DrugTypeController extends Controller
{
    public function all(Request $request)
    {
        $root = GoodsType::query()
            ->where('type', 'drug')
            ->orderBy('id', 'asc')
            ->first();
        $data = $root->select('id', 'name AS text', 'parentid', 'child', 'deleteable', 'editable')
            ->where(function ($query) use ($root) {
                $query->where('tree', 'like', "{$root->tree}-%")->orWhere('id', $root->id);
            })
            ->orderBy('id', 'ASC')
            ->get()
            ->toArray();
        return response_success(
            list_to_tree($data, 'id', 'parentid', 'children', $root->parentid)
        );
    }

    /**
     * 创建分类
     * @param DrugTypeRequest $request
     * @return JsonResponse
     */
    public function create(DrugTypeRequest $request)
    {
        $drug = GoodsType::query()->create(
            $request->formData()
        );
        return response_success($drug);
    }

    /**
     * 更新分类
     * @param DrugTypeRequest $request
     * @return JsonResponse
     */
    public function update(DrugTypeRequest $request)
    {
        $drug = GoodsType::query()->find(
            $request->input('id')
        );
        $drug->update(
            $request->formData()
        );
        return response_success($drug);
    }

    /**
     * 删除分类
     * @param DrugTypeRequest $request
     * @return JsonResponse
     */
    public function remove(DrugTypeRequest $request)
    {
        GoodsType::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
