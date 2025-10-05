<?php

namespace App\Http\Controllers\Web;

use Exception;
use App\Models\Goods;
use App\Models\GoodsType;
use App\Models\Inventory;
use App\Helpers\Attachment;
use Illuminate\Http\Request;
use App\Models\InventoryBatchs;
use App\Models\InventoryDetail;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\GoodsRequest;
use Illuminate\Database\Eloquent\Builder;

class GoodsController extends Controller
{
    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = Goods::query()
            ->with([
                'type',
                'units' => fn($query) => $query->orderByDesc('basic')->orderByDesc('id'),
                'alarms'
            ])
            ->when(request('type_id') && request('type_id') != 1, function ($query) {
                $query->whereIn('type_id', GoodsType::query()->find(request('type_id'))->getAllChild()->pluck('id'));
            })
            ->when(request('keyword'), function ($query) {
                $query->where('keyword', 'like', '%' . request('keyword') . '%');
            })
            ->when(request('warn_days_start') && request('warn_days_end'), function ($query) {
                $query->whereBetween('warn_days', [
                    request('warn_days_start'),
                    request('warn_days_end')
                ]);
            })
            ->when(request('inventory_number_start') && request('inventory_number_end'), function ($query) {
                $query->whereBetween('inventory_number', [
                    request('inventory_number_start'),
                    request('inventory_number_end')
                ]);
            })
            ->when(request('inventory_amount_start') && request('inventory_amount_end'), function ($query) {
                $query->whereBetween('inventory_amount', [
                    request('inventory_amount_start'),
                    request('inventory_amount_end')
                ]);
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 添加物品
     * @param GoodsRequest $request
     * @return JsonResponse
     */
    public function create(GoodsRequest $request): JsonResponse
    {
        $goods = Goods::query()->create(
            $request->getGoodsData()
        );

        $goods->unit()->sync(
            $request->getGoodsUnit()
        );

        $goods->alarm()->sync(
            $request->getGoodsAlarm()
        );

        $goods->load([
            'units',
            'alarms'
        ]);

        return response_success($goods);
    }

    /**
     * 更新物品
     * @param GoodsRequest $request
     * @return JsonResponse
     */
    public function update(GoodsRequest $request): JsonResponse
    {
        $goods = Goods::query()->find(
            $request->input('id')
        );

        // 同步信息
        $goods->unit()->sync(
            $request->getGoodsUnit()
        );
        $goods->alarm()->sync(
            $request->getGoodsAlarm()
        );

        $goods->update(
            $request->getGoodsData()
        );

        $goods->load([
            'units',
            'alarms'
        ]);

        return response_success($goods);
    }

    /**
     * 上传图片
     * @param Request $request
     * @param Attachment $attachment
     * @return JsonResponse
     */
    public function upload(Request $request, Attachment $attachment): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg',
        ], [
            'file.required' => '请选择上传文件',
            'file.file'     => '上传文件必须是图片',
            'file.mimes'    => '上传文件类型不符合要求',
        ]);
        $file = $attachment->upload($request->file('file'), 'goods');
        $path = get_attachment_url($file['file_path']);
        return response_success([
            'path' => $path
        ]);
    }

    /**
     * 删除商品
     * @param GoodsRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function remove(GoodsRequest $request): JsonResponse
    {
        Goods::query()->whereIn('id', explode(',', $request->input('ids')))->delete();
        return response_success();
    }

    /**
     * 批量启用项目
     * @param GoodsRequest $request
     * @return JsonResponse
     */
    public function enable(GoodsRequest $request): JsonResponse
    {
        Goods::query()
            ->whereIn('id', $request->input('ids'))
            ->update([
                'disabled' => 0
            ]);
        return response_success();
    }

    /**
     * 批量禁用物品
     * @param GoodsRequest $request
     * @return JsonResponse
     */
    public function disable(GoodsRequest $request): JsonResponse
    {
        Goods::query()
            ->whereIn('id', $request->input('ids'))
            ->update([
                'disabled' => 1
            ]);
        return response_success();
    }

    /**
     * 商品[库存分布]
     * @param GoodsRequest $request
     * @return JsonResponse
     */
    public function inventory(GoodsRequest $request): JsonResponse
    {
        $inventory = Inventory::query()
            ->with([
                'goods.basicUnit.unit',
                'warehouse:id,name'
            ])
            ->where('goods_id', $request->input('goods_id'))
            ->get();

        $footer = [
            [
                'warehouse_id' => '合计:',
                'number'       => $inventory->sum('number'),
                'amount'       => $inventory->sum('amount'),
            ]
        ];

        return response_success([
            'rows'   => $inventory,
            'footer' => $footer
        ]);
    }

    /**
     * 物品库存明细
     * @param GoodsRequest $request
     * @return JsonResponse
     */
    public function inventoryDetail(GoodsRequest $request): JsonResponse
    {
        $sort  = $request->input('sort', 'id');
        $rows  = $request->input('rows', 10);
        $order = $request->input('order', 'desc');
        $query = InventoryDetail::query()
            ->where('goods_id', $request->input('goods_id'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 物品批次管理
     * @param GoodsRequest $request
     * @return JsonResponse
     */
    public function inventoryBatch(GoodsRequest $request): JsonResponse
    {
        $sort  = $request->input('sort', 'id');
        $rows  = $request->input('rows', 10);
        $order = $request->input('order', 'desc');
        $query = InventoryBatchs::query()
            ->with([
                'warehouse:id,name',
            ])
            ->where('goods_id', $request->input('goods_id'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 查询（combogrid）
     * @param Request $request
     * @return JsonResponse
     */
    public function query(Request $request): JsonResponse
    {
        $rows         = $request->input('rows', 10);
        $warehouse_id = $request->input('warehouse_id');
        $query        = Goods::query()
            ->with([
                'type:id,name',
                'units.unit'
            ])
            ->select([
                'goods.id',
                'goods.name',
                'goods.type_id',
                'goods.specs',
                'goods.approval_number',
            ])
            // 查询指定仓库
            ->when($warehouse_id, function (Builder $query) use ($warehouse_id) {
                $query->selectRaw('(CASE WHEN `cy_inventory`.`number` IS NULL THEN 0 ELSE `cy_inventory`.`number` END) AS inventory_number')
                    ->leftJoin('inventory', function ($join) use ($warehouse_id) {
                        $join->on('goods.id', '=', 'inventory.goods_id')->where('inventory.warehouse_id', $warehouse_id);
                    });
            })
            // 不限仓库
            ->when(!$warehouse_id, function (Builder $query) {
                $query->addSelect(['goods.inventory_number']);
            })
            ->where('goods.disabled', 0)
            // 查询指定ID物品
            ->when($request->input('id'), function (Builder $query) use ($request) {
                $query->where('goods.id', $request->input('id'));
            })
            // 指定查询[药品]或[物品]
            ->when($request->input('type') && $request->input('type') !== 'root', function (Builder $query) use ($request) {
                $query->where('goods.is_drug', $request->input('type') == 'drug' ? 1 : 0);
            })
            ->when($request->input('keyword'), function (Builder $query) use ($request) {
                $query->where('goods.keyword', 'like', '%' . $request->input('keyword') . '%');
            })
            ->when($request->input('type_id') && $request->input('type_id') !== 1, function (Builder $query) use ($request) {
                $query->whereIn('goods.type_id', GoodsType::query()->find($request->input('type_id'))->getAllChild()->pluck('id'));
            })
            // 过滤零库存
            ->when($request->input('filter'), function (Builder $query) use ($warehouse_id) {
                $warehouse_id
                    ? $query->where('inventory.number', '>', 0)
                    : $query->where('goods.inventory_number', '>', 0);
            })
            ->orderBy('goods.id', 'desc')
            ->paginate($rows);

        // 加载批号
        if ($request->input('batchs')) {
            $query->load(['inventoryBatchs' => function ($query) use ($warehouse_id) {
                // 指定仓库
                $query->when($warehouse_id, function ($query) use ($warehouse_id) {
                    $query->where('warehouse_id', $warehouse_id);
                })->orderBy('id', 'ASC');
            }, 'inventoryBatchs.manufacturer']);
        }

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 查询物品批次(退货入库等使用)
     * @param GoodsRequest $request
     * @return JsonResponse
     */
    public function queryBatchs(GoodsRequest $request): JsonResponse
    {
        $rows         = $request->input('rows', 10);
        $sort         = $request->input('sort', 'id');
        $order        = $request->input('order', 'desc');
        $keyword      = $request->input('keyword');
        $warehouse_id = $request->input('warehouse_id');

        $query = InventoryBatchs::query()
            ->where('goods_id', $request->input('goods_id'))
            ->when($keyword, fn(Builder $query) => $query->where('batch_code', 'like', '%' . $keyword . '%'))
            ->when($warehouse_id, fn(Builder $query) => $query->where('warehouse_id', $warehouse_id))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }
}
