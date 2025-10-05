<?php

namespace App\Http\Controllers\Web;

use App\Models\GoodsType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\GoodsTypeRequest;

class GoodsTypeController extends Controller
{
    /**
     * 物品分类
     * @param Request $request
     * @return JsonResponse
     */
    public function all(Request $request)
    {
        $root = GoodsType::query()
            ->where('type', 'goods')
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
     * 创建物品分类
     * @param GoodsTypeRequest $request
     * @return JsonResponse
     */
    public function create(GoodsTypeRequest $request)
    {
        $type = GoodsType::query()->create(
            $request->formData()
        );
        return response_success($type);
    }

    /**
     * 更新物品分类
     * @param GoodsTypeRequest $request
     * @return JsonResponse
     */
    public function update(GoodsTypeRequest $request)
    {
        $type = GoodsType::find($request->input('id'));

        $type->update([
            'name' => $request->input('name')
        ]);

        return response_success($type);
    }

    /**
     * 删除分类
     * @param GoodsTypeRequest $request
     * @return JsonResponse
     */
    public function remove(GoodsTypeRequest $request)
    {
        GoodsType::find($request->input('id'))->delete();
        return response_success();
    }
}
