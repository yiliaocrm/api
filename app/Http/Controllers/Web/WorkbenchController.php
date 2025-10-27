<?php

namespace App\Http\Controllers\Web;

use App\Models\Menu;
use App\Models\Goods;
use App\Models\Followup;
use App\Models\GoodsType;
use App\Models\Reception;
use App\Models\Appointment;
use App\Models\InventoryBatchs;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\WorkbenchRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class WorkbenchController extends Controller
{
    /**
     * 流水牌
     * @param WorkbenchRequest $request
     * @return JsonResponse
     */
    public function menu(WorkbenchRequest $request): JsonResponse
    {
        $user              = user();
        $user->permissions = $user->getMergedPermissions();

        $menu = Menu::query()
            ->where('parentid', 1)
            ->where('type', 'web')
            ->where('menu_type', 'menu')
            ->when(!$user->isSuperUser(), fn(Builder $query) => $query->whereIn('permission', array_keys(array_filter($user->permissions ?? []))))
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        // 获取对应业务数据
        $menu->each(function ($item) use ($request) {
            $item->count = $request->getMenuCount($item->permission);
        });

        return response_success($menu);
    }

    /**
     * 今日就诊
     * @param Request $request
     * @return JsonResponse
     */
    public function today(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'appointments.created_at');
        $order = $request->input('order', 'desc');
        $query = Appointment::query()
            ->with([
                'customer:id,name,idcard,sex,age',
                'consultant:id,name',
                'doctor:id,name',
                'reception',
                'technician:id,name',
                'department:id,name',
            ])
            ->when($request->input('arrival') && $request->input('arrival') === 'true', fn($query) => $query->whereNotNull('arrival_time'))
            ->when($request->input('arrival') && $request->input('arrival') === 'false', fn($query) => $query->whereNull('arrival_time'))
            ->when($request->input('status'), fn($query) => $query->where('status', $request->input('status')))
            ->where('date', $request->input('date', date('Y-m-d')))
            ->orderBy($sort, $order)
            ->paginate($rows);

        $query->append(['status_text', 'type_text']);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 回访提醒
     * @param Request $request
     * @return JsonResponse
     */
    public function followup(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $query = Followup::query()
            ->with([
                'customer:id,idcard,name',
                'user:id,name',
                'followupType',
                'followupTool',
                'executeUserInfo:id,name',
                'followupUserInfo:id,name',
            ])
            ->orderBy('followup.created_at', 'desc')
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 分诊接待
     * @param WorkbenchRequest $request
     * @return JsonResponse
     */
    public function reception(WorkbenchRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');

        $builder = Reception::query()
            ->with([
                'customer:id,name,idcard',
                'department:id,name',
                'receptionType:id,name',
                'receptionItems',
                'medium:id,name',
                'consultantUser:id,name',
                'ekUserRelation:id,name',
                'receptionUser:id,name',
                'doctorUser:id,name',
                'user:id,name',
            ])
            ->select([
                'reception.*'
            ])
            ->leftJoin('customer', 'reception.customer_id', '=', 'customer.id')
            ->whereBetween('reception.created_at', [
                Carbon::parse($request->input('created_at.0'))->startOfDay(),
                Carbon::parse($request->input('created_at.1'))->endOfDay()
            ])
            ->queryConditions('WorkbenchReception')
            ->when($keyword, fn(Builder $query) => $query->whereLike('customer.keyword', '%' . $keyword . '%'))
            // 权限限制
            ->when(!user()->hasAnyAccess(['superuser', 'reception.view.all']), function (Builder $query) {
                $ids = user()->getReceptionViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('reception.user_id', $ids)->orWhere('reception.reception', $ids);
                });
            })
            ->orderBy('reception.' . $sort, $order);

        // 执行分页
        $query = $builder->paginate($rows);
        $query->append(['status_text']);

        return response_success([
            'rows'      => $query->items(),
            'total'     => $query->total(),
            'dashboard' => $request->getReceptionDashboard($builder)
        ]);
    }

    /**
     * 预约列表
     * @param WorkbenchRequest $request
     * @return JsonResponse
     */
    public function appointment(WorkbenchRequest $request): JsonResponse
    {
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $rows    = $request->input('rows', 10);
        $status  = $request->input('status');
        $keyword = $request->input('keyword');
        $query   = Appointment::query()
            ->with([
                'doctor:id,name',
                'consultant:id,name',
                'technician:id,name',
                'customer:id,name,idcard',
                'department:id,name',
                'createUser:id,name'
            ])
            ->select([
                'appointments.*'
            ])
            ->leftJoin('customer', 'customer.id', '=', 'appointments.customer_id')
            ->queryConditions('WorkbenchAppointment')
            ->whereBetween('appointments.created_at', [
                Carbon::parse($request->input('created_at.0'))->startOfDay(),
                Carbon::parse($request->input('created_at.1'))->endOfDay()
            ])
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            ->when($request->has('status'), fn(Builder $query) => $query->where('status', $status))
            ->orderBy("appointments.{$sort}", $order)
            ->paginate($rows);

        $query->append(['status_text', 'type_text']);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 库存预警
     * @param WorkbenchRequest $request
     * @return JsonResponse
     */
    public function inventoryAlarm(WorkbenchRequest $request): JsonResponse
    {
        $rows         = $request->input('rows', 10);
        $sort         = $request->input('sort', 'id');
        $order        = $request->input('order', 'desc');
        $name         = $request->input('name');
        $status       = $request->input('status');
        $type_id      = $request->input('type_id');
        $filterable   = $request->input('filterable');
        $warehouse_id = $request->input('warehouse_id');

        $query = Goods::query()
            ->with(['type', 'units'])
            ->select(['goods.id', 'goods.type_id', 'goods.name', 'goods.specs'])
            // 合计预警
            ->when(!$warehouse_id, fn(Builder $query) => $query->addSelect(['goods.max', 'goods.min', 'goods.inventory_number']))
            // 分仓预警
            ->when($warehouse_id, fn(Builder $query) => $request->applyWarehouseSpecificQuery($query, $warehouse_id))
            ->when($type_id && $type_id != 1, function (Builder $query) use ($type_id) {
                $query->whereIn('goods.type_id', GoodsType::query()->find($type_id)->getAllChild()->pluck('id'));
            })
            ->when($name, fn(Builder $query) => $query->where('goods.name', 'like', '%' . $name . '%'))
            // 预警状态:库存正常
            ->when($status == 'normal', fn(Builder $query) => $request->applyInventoryNormalStatus($query, $warehouse_id))
            // 预警状态:库存过剩
            ->when($status == 'high', fn(Builder $query) => $request->applyInventoryHighStatus($query, $warehouse_id))
            // 预警状态:库存不足
            ->when($status == 'low', fn(Builder $query) => $request->applyInventoryLowStatus($query, $warehouse_id))
            // 过滤库存为空
            ->when($filterable == 'hide', fn(Builder $query) => $request->applyInventoryFilterEmpty($query, $warehouse_id))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 过期预警
     * @param WorkbenchRequest $request
     * @return JsonResponse
     */
    public function inventoryExpiry(WorkbenchRequest $request): JsonResponse
    {
        $rows         = $request->input('rows', 10);
        $sort         = $request->input('sort', 'id');
        $order        = $request->input('order', 'desc');
        $name         = $request->input('name');
        $status       = $request->input('status');
        $type_id      = $request->input('type_id');
        $expiry_diff  = $request->input('expiry_diff');
        $warehouse_id = $request->input('warehouse_id');

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
            ->when($type_id && $type_id != 1, function (Builder $query) use ($type_id) {
                $query->whereIn('goods.type_id', GoodsType::query()->find($type_id)->getAllChild()->pluck('id'));
            })
            ->where('inventory_batchs.number', '>', 0)
            ->whereNotNull('inventory_batchs.expiry_date')
            ->when($name, fn(Builder $query) => $query->where('goods.name', 'like', '%' . $name . '%'))
            ->when($warehouse_id, fn(Builder $query) => $query->where('inventory_batchs.warehouse_id', $warehouse_id))
            // 正常
            ->when($status == 'normal', function (Builder $query) {
                $query->where('inventory_batchs.expiry_date', '>=', DB::raw('curdate()'))
                    ->whereNotBetween(DB::raw('curdate()'), [
                        DB::raw('DATE_SUB(cy_inventory_batchs.expiry_date, INTERVAL cy_goods.warn_days DAY)'),
                        DB::raw('cy_inventory_batchs.expiry_date')
                    ]);
            })
            // 预警期内
            ->when($status == 'expiring', function (Builder $query) {
                $query->where('goods.warn_days', '<>', 0)
                    ->whereBetween(DB::raw('curdate()'), [
                        DB::raw('DATE_SUB(cy_inventory_batchs.expiry_date, INTERVAL cy_goods.warn_days DAY)'),
                        DB::raw('cy_inventory_batchs.expiry_date')
                    ]);
            })
            // 已经过期
            ->when($status == 'expired', fn(Builder $query) => $query->where('inventory_batchs.expiry_date', '<', DB::raw('curdate()')))
            // 剩余天数
            ->when($expiry_diff, fn(Builder $query) => $query->whereRaw('DATEDIFF(cy_inventory_batchs.expiry_date, curdate()) <= ?', $expiry_diff))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }
}
