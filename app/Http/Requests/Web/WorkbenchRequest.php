<?php

namespace App\Http\Requests\Web;

use App\Models\Goods;
use App\Models\Customer;
use App\Models\Reception;
use App\Models\Appointment;
use App\Models\InventoryBatchs;
use App\Rules\Web\SceneRule;
use App\Models\ReceptionType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

class WorkbenchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'birthday' => $this->getBirthdayRules(),
            'reception' => $this->getReceptionRules(),
            'appointment' => $this->getAppointmentRules(),
            default => [],
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'birthday' => $this->getBirthdayMessages(),
            'reception' => $this->getReceptionMessages(),
            'appointment' => $this->getAppointmentMessages(),
            default => []
        };
    }

    private function getReceptionRules(): array
    {
        return [
            'filters'      => [
                'nullable',
                'array',
                new SceneRule('WorkbenchReception')
            ],
            'created_at'   => 'required|array|size:2',
            'created_at.*' => 'required|date|date_format:Y-m-d',
        ];
    }

    private function getBirthdayRules(): array
    {
        return [
            'keyword'    => 'nullable|string|max:255',
            'birthday'   => 'required|array|size:2',
            'birthday.*' => 'required|date|date_format:Y-m-d',
        ];
    }

    private function getBirthdayMessages(): array
    {
        return [
            'keyword.string'         => '[顾客信息]格式不正确',
            'keyword.max'            => '[顾客信息]不能超过255个字符',
            'birthday.required'      => '[生日范围]不能为空',
            'birthday.array'         => '[生日范围]格式不正确',
            'birthday.size'          => '[生日范围]必须包含开始和结束日期',
            'birthday.*.required'    => '[生日范围]格式不正确',
            'birthday.*.date'        => '[生日范围]格式不正确',
            'birthday.*.date_format' => '[生日范围]格式必须为Y-m-d',
        ];
    }

    private function getReceptionMessages(): array
    {
        return [
            'filters.array'            => '[场景化筛选条件]格式不正确',
            'created_at.required'      => '[查询时间]不能为空',
            'created_at.array'         => '[查询时间]格式不正确',
            'created_at.size'          => '[查询时间]格式不正确',
            'created_at.*.required'    => '[查询时间]格式不正确',
            'created_at.*.date'        => '[查询时间]格式不正确',
            'created_at.*.date_format' => '[查询时间]格式不正确',
        ];
    }

    private function getAppointmentRules(): array
    {
        return [
            'filters'      => [
                'nullable',
                'array',
                new SceneRule('WorkbenchAppointment')
            ],
            'created_at'   => 'required|array|size:2',
            'created_at.*' => 'required|date|date_format:Y-m-d',
        ];
    }

    private function getAppointmentMessages(): array
    {
        return [
            'filters.array'            => '[场景化筛选条件]格式不正确',
            'created_at.required'      => '[查询时间]不能为空',
            'created_at.array'         => '[查询时间]格式不正确',
            'created_at.size'          => '[查询时间]格式不正确',
            'created_at.*.required'    => '[查询时间]格式不正确',
            'created_at.*.date'        => '[查询时间]格式不正确',
            'created_at.*.date_format' => '[查询时间]格式不正确',
        ];
    }

    /**
     * 获取流水牌数据统计
     * @param string $permission
     * @return int
     */
    public function getMenuCount(string $permission): int
    {
        $todayWorkbench  = Appointment::query()->whereDate('date', today())->count();
        $receptionManage = Reception::query()->whereDate('created_at', today())->count();
        return match ($permission) {
            'workbench.today' => $todayWorkbench,
            'workbench.alarm' => $this->getInventoryAlarmCount(),
            'workbench.expiry' => $this->getInventoryExpiryCount(),
            'workbench.birthday' => $this->getTodayBirthdayCount(),
            'workbench.reception' => $receptionManage,
            default => 0,
        };
    }

    /**
     * 获取库存预警数量（库存不足 + 库存过剩）
     * @return int
     */
    private function getInventoryAlarmCount(): int
    {
        return Goods::query()
            ->where(function (Builder $query) {
                // 库存不足：min <> 0 且 min > inventory_number
                $query->where(function (Builder $subQuery) {
                    $subQuery->where('goods.min', '<>', 0)
                        ->where('goods.min', '>', DB::raw('inventory_number'));
                })
                    // 或者库存过剩：max <> 0 且 max < inventory_number
                    ->orWhere(function (Builder $subQuery) {
                        $subQuery->where('goods.max', '<>', 0)
                            ->where('goods.max', '<', DB::raw('inventory_number'));
                    });
            })
            ->count();
    }

    /**
     * 获取今日生日顾客数量
     * @return int
     */
    private function getTodayBirthdayCount(): int
    {
        $today = date('m-d');

        return Customer::query()
            ->whereNotNull('birthday')
            ->where(DB::raw("DATE_FORMAT(birthday, '%m-%d')"), $today)
            // 权限限制
            ->when(!user()->hasAnyAccess(['superuser', 'customer.view.all']), function (Builder $query) {
                $ids = user()->getCustomerViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('ascription', $ids)->orWhereIn('consultant', $ids);
                });
            })
            ->count();
    }

    /**
     * 获取过期预警数量（预警期内 + 已经过期）
     * @return int
     */
    private function getInventoryExpiryCount(): int
    {
        return InventoryBatchs::query()
            ->leftJoin('goods', 'goods.id', '=', 'inventory_batchs.goods_id')
            ->where('inventory_batchs.number', '>', 0)
            ->whereNotNull('inventory_batchs.expiry_date')
            ->where(function (Builder $query) {
                // 预警期内：warn_days <> 0 且当前日期在预警期内
                $query->where(function (Builder $subQuery) {
                    $subQuery->where('goods.warn_days', '<>', 0)
                        ->whereBetween(DB::raw('curdate()'), [
                            DB::raw('DATE_SUB(cy_inventory_batchs.expiry_date, INTERVAL cy_goods.warn_days DAY)'),
                            DB::raw('cy_inventory_batchs.expiry_date')
                        ]);
                })
                    // 或者已经过期
                    ->orWhere(function (Builder $subQuery) {
                        $subQuery->where('inventory_batchs.expiry_date', '<', DB::raw('curdate()'));
                    });
            })
            ->count();
    }

    /**
     * 获取分诊接待类型统计
     * @param Builder $builder
     * @return Collection
     */
    public function getReceptionDashboard(Builder $builder): Collection
    {
        // 获取分诊类型统计
        $types  = ReceptionType::query()->orderBy('id')->get();
        $counts = $builder->clone()
            ->select('reception.type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('reception.type')
            ->reorder() // 清除排序，避免影响groupBy
            ->pluck('count', 'type');

        return $types->map(function ($type) use ($counts) {
            return [
                'id'    => $type->id,
                'name'  => $type->name,
                'count' => $counts->get($type->id, 0)
            ];
        });
    }

    /**
     * 分仓预警
     * @param Builder $query
     * @param int $warehouse_id
     * @return Builder
     */
    public function applyWarehouseSpecificQuery(Builder $query, int $warehouse_id): Builder
    {
        return $query->addSelect(['warehouse_alarm.max', 'warehouse_alarm.min'])
            ->selectRaw('IFNULL(cy_inventory.number, 0) as inventory_number')
            ->leftJoin('inventory', function (JoinClause $join) use ($warehouse_id) {
                $join->on('inventory.goods_id', '=', 'goods.id')
                    ->where('inventory.warehouse_id', $warehouse_id);
            })
            ->leftJoin('warehouse_alarm', function (JoinClause $join) use ($warehouse_id) {
                $join->on('warehouse_alarm.goods_id', '=', 'goods.id')
                    ->where('warehouse_alarm.warehouse_id', $warehouse_id);
            });
    }

    /**
     * 应用库存预警状态筛选 - 库存正常
     * @param Builder $query
     * @param int|null $warehouse_id
     * @return Builder
     */
    public function applyInventoryNormalStatus(Builder $query, ?int $warehouse_id): Builder
    {
        if ($warehouse_id) {
            return $query->where(function (Builder $query) {
                $query->whereBetween('inventory.number', [DB::raw('cy_warehouse_alarm.min'), DB::raw('cy_warehouse_alarm.max')])
                    ->orWhere(function (Builder $query) {
                        $query->where('warehouse_alarm.max', 0)->where('inventory.number', '>=', DB::raw('cy_warehouse_alarm.min'));
                    })
                    ->orWhere(function (Builder $query) {
                        $query->where('warehouse_alarm.min', 0)->where('inventory.number', '<=', DB::raw('cy_warehouse_alarm.max'));
                    });
            });
        }

        return $query->where(function (Builder $query) {
            $query->whereBetween('goods.inventory_number', [DB::raw('cy_goods.min'), DB::raw('cy_goods.max')])
                ->orWhere(function (Builder $query) {
                    $query->where('goods.max', 0)->where('goods.inventory_number', '>=', DB::raw('cy_goods.min'));
                })
                ->orWhere(function (Builder $query) {
                    $query->where('goods.min', 0)->where('goods.inventory_number', '<=', DB::raw('cy_goods.max'));
                });
        });
    }

    /**
     * 应用库存预警状态筛选 - 库存过剩
     * @param Builder $query
     * @param int|null $warehouse_id
     * @return Builder
     */
    public function applyInventoryHighStatus(Builder $query, ?int $warehouse_id): Builder
    {
        if ($warehouse_id) {
            return $query->where('warehouse_alarm.max', '<>', 0)
                ->where('warehouse_alarm.max', '<', DB::raw('cy_inventory.number'));
        }

        return $query->where('goods.max', '<>', 0)
            ->where('goods.max', '<', DB::raw('inventory_number'));
    }

    /**
     * 应用库存预警状态筛选 - 库存不足
     * @param Builder $query
     * @param int|null $warehouse_id
     * @return Builder
     */
    public function applyInventoryLowStatus(Builder $query, ?int $warehouse_id): Builder
    {
        if ($warehouse_id) {
            return $query->where('warehouse_alarm.min', '<>', 0)
                ->where('warehouse_alarm.min', '>', DB::raw('cy_inventory.number'));
        }

        return $query->where('goods.min', '<>', 0)
            ->where('goods.min', '>', DB::raw('inventory_number'));
    }

    /**
     * 过滤库存为空的商品
     * @param Builder $query
     * @param int|null $warehouse_id
     * @return Builder
     */
    public function applyInventoryFilterEmpty(Builder $query, ?int $warehouse_id): Builder
    {
        if ($warehouse_id) {
            return $query->where('inventory.number', '>', 0);
        }

        return $query->where('goods.inventory_number', '>', 0);
    }
}
