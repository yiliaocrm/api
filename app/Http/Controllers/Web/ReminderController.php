<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Goods;
use App\Models\GoodsType;
use App\Models\InventoryBatchs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReminderController extends Controller
{
    /**
     * 库存预警
     * @param Request $request
     * @return JsonResponse
     */
    public function inventoryAlarm(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $data  = [];

        $query = Goods::query()
            ->with(['type', 'units'])
            ->select(['goods.id', 'goods.type_id', 'goods.name', 'goods.specs'])
            // 合计预警
            ->when(!$request->input('warehouse_id'), function (Builder $query) {
                $query->addSelect(['goods.max', 'goods.min', 'goods.inventory_number']);
            })
            // 分仓预警
            ->when($request->input('warehouse_id'), function (Builder $query) use ($request) {
                $query->addSelect(['warehouse_alarm.max', 'warehouse_alarm.min'])
                    ->selectRaw('IFNULL(cy_inventory.number, 0) as inventory_number')
                    ->leftJoin('inventory', function (JoinClause $join) use ($request) {
                        $join->on('inventory.goods_id', '=', 'goods.id')->where('inventory.warehouse_id', $request->input('warehouse_id'));
                    })
                    ->leftJoin('warehouse_alarm', function (JoinClause $join) use ($request) {
                        $join->on('warehouse_alarm.goods_id', '=', 'goods.id')->where('warehouse_alarm.warehouse_id', $request->input('warehouse_id'));
                    });
            })
            ->when($request->input('type_id') && $request->input('type_id') != 1, function (Builder $query) use ($request) {
                $query->whereIn('goods.type_id', GoodsType::query()->find($request->input('type_id'))->getAllChild()->pluck('id'));
            })
            ->when($request->input('name'), function (Builder $query) use ($request) {
                $query->where('goods.name', 'like', '%' . $request->input('name') . '%');
            })
            // 预警状态:库存正常
            ->when($request->input('status') && $request->input('status') == 'normal', function (Builder $query) use ($request) {
                $request->input('warehouse_id')
                    ?
                    $query->where(function (Builder $query) {
                        $query->whereBetween('inventory.number', [DB::raw('cy_warehouse_alarm.min'), DB::raw('cy_warehouse_alarm.max')])
                            ->orWhere(function (Builder $query) {
                                $query->where('warehouse_alarm.max', 0)->where('inventory.number', '>=', DB::raw('cy_warehouse_alarm.min'));
                            })
                            ->orWhere(function (Builder $query) {
                                $query->where('warehouse_alarm.min', 0)->where('inventory.number', '<=', DB::raw('cy_warehouse_alarm.max'));
                            });
                    })
                    :
                    $query->where(function (Builder $query) {
                        $query->whereBetween('goods.inventory_number', [DB::raw('cy_goods.min'), DB::raw('cy_goods.max')])
                            ->orWhere(function (Builder $query) {
                                $query->where('goods.max', 0)->where('goods.inventory_number', '>=', DB::raw('cy_goods.min'));
                            })
                            ->orWhere(function (Builder $query) {
                                $query->where('goods.min', 0)->where('goods.inventory_number', '<=', DB::raw('cy_goods.max'));
                            });
                    });
            })
            // 预警状态:库存过剩
            ->when($request->input('status') && $request->input('status') == 'high', function (Builder $query) use ($request) {
                $request->input('warehouse_id')
                    ? $query->where('warehouse_alarm.max', '<>', 0)->where('warehouse_alarm.max', '<', DB::raw('cy_inventory.number'))
                    : $query->where('goods.max', '<>', 0)->where('goods.max', '<', DB::raw('inventory_number'));
            })
            // 预警状态:库存不足
            ->when($request->input('status') && $request->input('status') == 'low', function (Builder $query) use ($request) {
                $request->input('warehouse_id')
                    ? $query->where('warehouse_alarm.min', '<>', 0)->where('warehouse_alarm.min', '>', DB::raw('cy_inventory.number'))
                    : $query->where('goods.min', '<>', 0)->where('goods.min', '>', DB::raw('inventory_number'));
            })
            // 过滤库存为空
            ->when($request->input('filterable') && $request->input('filterable') == 'hide', function (Builder $query) use ($request) {
                $request->input('warehouse_id')
                    ? $query->where('inventory.number', '>', 0)
                    : $query->where('goods.inventory_number', '>', 0);
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        if ($query) {
            $data['rows']  = $query->items();
            $data['total'] = $query->total();
        } else {
            $data['rows']  = [];
            $data['total'] = 0;
        }

        return response_success($data);
    }

    /**
     * 过期预警
     * @param Request $request
     * @return JsonResponse
     */
    public function inventoryExpiry(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $data  = [];

        $query = InventoryBatchs::query()
            ->select([
                'inventory_batchs.id',
                'goods.name',
                'goods.specs',
                'goods.warn_days',
                'inventory_batchs.warehouse_id',
                'inventory_batchs.manufacturer_name',
                'inventory_batchs.batch_code',
                'inventory_batchs.number',
                'inventory_batchs.unit_name',
                'inventory_batchs.production_date',
                'inventory_batchs.expiry_date',
                'inventory_batchs.created_at',
            ])
            ->addSelect(DB::raw('DATEDIFF(cy_inventory_batchs.expiry_date, curdate()) as expiry_diff'))
            ->leftJoin('goods', 'goods.id', '=', 'inventory_batchs.goods_id')
            ->when($request->input('type_id') && $request->input('type_id') != 1, function (Builder $query) use ($request) {
                $query->whereIn('goods.type_id', GoodsType::query()->find($request->input('type_id'))->getAllChild()->pluck('id'));
            })
            ->where('inventory_batchs.number', '>', 0)
            ->whereNotNull('inventory_batchs.expiry_date')
            ->when($request->input('name'), function (Builder $query) use ($request) {
                $query->where('goods.name', 'like', '%' . $request->input('name') . '%');
            })
            ->when($request->input('warehouse_id'), function (Builder $query) use ($request) {
                $query->where('inventory_batchs.warehouse_id', $request->input('warehouse_id'));
            })
            // 正常
            ->when($request->input('status') && $request->input('status') == 'normal', function (Builder $query) use ($request) {
                $query->where('inventory_batchs.expiry_date', '>=', DB::raw('curdate()'))
                    ->whereNotBetween(DB::raw('curdate()'), [
                        DB::raw('DATE_SUB(cy_inventory_batchs.expiry_date, INTERVAL cy_goods.warn_days DAY)'),
                        DB::raw('cy_inventory_batchs.expiry_date')
                    ]);
            })
            // 预警期内
            ->when($request->input('status') && $request->input('status') == 'expiring', function (Builder $query) use ($request) {
                $query->where('goods.warn_days', '<>', 0)
                    ->whereBetween(DB::raw('curdate()'), [
                        DB::raw('DATE_SUB(cy_inventory_batchs.expiry_date, INTERVAL cy_goods.warn_days DAY)'),
                        DB::raw('cy_inventory_batchs.expiry_date')
                    ]);
            })
            // 已经过期
            ->when($request->input('status') && $request->input('status') == 'expired', function (Builder $query) use ($request) {
                $query->where('inventory_batchs.expiry_date', '<', DB::raw('curdate()'));
            })
            // 剩余天数
            ->when($request->input('expiry_diff'), function (Builder $query) use ($request) {
                $query->whereRaw('DATEDIFF(cy_inventory_batchs.expiry_date, curdate()) <= ?', $request->input('expiry_diff'));
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        if ($query) {
            $data['rows']  = $query->items();
            $data['total'] = $query->total();
        } else {
            $data['rows']  = [];
            $data['total'] = 0;
        }

        return response_success($data);
    }
}
