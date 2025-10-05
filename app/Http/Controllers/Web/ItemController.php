<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Item\CreateRequest;
use App\Http\Requests\Item\InfoRequest;
use App\Http\Requests\Item\RemoveRequest;
use App\Http\Requests\Item\UpdateRequest;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    /**
     * 咨询项目列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function manage(Request $request)
    {
        $data = Item::query()->where('parentid', $request->input('id', 0))->get();
        $data->each(function ($v) {
            $v->state = $v->child ? 'closed' : 'open';
        });
        return response_success($data);
    }

    /**
     * 查看项目
     * @param InfoRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function info(InfoRequest $request)
    {
        $data = Item::query()->find(
            $request->input('id')
        );
        return response_success($data);
    }

    /**
     * 创建项目
     * @param CreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateRequest $request)
    {
        $items = $request->formData();
        $ids   = [];

        foreach ($items as $item) {
            $id = Item::query()->create($item);
            array_push($ids, $id);
        }

        return response_success($ids);
    }

    /**
     * 更新数据
     * @param UpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRequest $request)
    {
        $data = Item::query()->find($request->input('id'));
        $data->update(
            $request->formData()
        );
        return response_success($data);
    }

    /**
     * 删除项目
     * @param RemoveRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function remove(RemoveRequest $request)
    {
        Item::query()->find($request->input('id'))->delete();
        return response_success();
    }

    /**
     * 移动
     * @param Request $request
     */
    public function move(Request $request)
    {
		Item::query()->find($request->input('id'))->update([
			'parentid' => $request->input('parentid')
		]);
    }
}
