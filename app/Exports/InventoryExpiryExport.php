<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\GoodsType;
use App\Models\InventoryBatchs;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

// excel
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class InventoryExpiryExport implements Responsable, WithColumnWidths, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '过期预警.xlsx';

    /**
     * @var $request Request
     */
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        return InventoryBatchs::query()
            ->select([
                'warehouse.name as warehouse_name',
                'inventory_batchs.goods_name',
                'inventory_batchs.specs',
                'inventory_batchs.manufacturer_name',
                'inventory_batchs.batch_code',
                'inventory_batchs.number',
                'inventory_batchs.unit_name',
                'goods.warn_days',
                'inventory_batchs.production_date',
                'inventory_batchs.expiry_date',
                'inventory_batchs.created_at',
            ])
            ->addSelect(DB::raw('DATEDIFF(cy_inventory_batchs.expiry_date, curdate()) as expiry_diff'))
            ->leftJoin('warehouse', 'warehouse.id', '=', 'inventory_batchs.warehouse_id')
            ->leftJoin('goods', 'goods.id', '=', 'inventory_batchs.goods_id')
            ->when($this->request->input('type_id') && $this->request->input('type_id') != 1, function (Builder $query) {
                $query->whereIn('goods.type_id', GoodsType::query()->find($this->request->input('type_id'))->getAllChild()->pluck('id'));
            })
            ->where('inventory_batchs.number', '>', 0)
            ->whereNotNull('inventory_batchs.expiry_date')
            ->when($this->request->input('name'), function (Builder $query) {
                $query->where('goods.name', 'like', '%' . $this->request->input('name') . '%');
            })
            ->when($this->request->input('warehouse_id'), function (Builder $query) {
                $query->where('inventory_batchs.warehouse_id', $this->request->input('warehouse_id'));
            })
            // 正常
            ->when($this->request->input('status') && $this->request->input('status') == 'normal', function (Builder $query) {
                $query->where('inventory_batchs.expiry_date', '>=', DB::raw('curdate()'))
                    ->whereNotBetween(DB::raw('curdate()'), [
                        DB::raw('DATE_SUB(cy_inventory_batchs.expiry_date, INTERVAL cy_goods.warn_days DAY)'),
                        DB::raw('cy_inventory_batchs.expiry_date')
                    ]);
            })
            // 预警期内
            ->when($this->request->input('status') && $this->request->input('status') == 'expiring', function (Builder $query) {
                $query->where('goods.warn_days', '<>', 0)
                    ->whereBetween(DB::raw('curdate()'), [
                        DB::raw('DATE_SUB(cy_inventory_batchs.expiry_date, INTERVAL cy_goods.warn_days DAY)'),
                        DB::raw('cy_inventory_batchs.expiry_date')
                    ]);
            })
            // 已经过期
            ->when($this->request->input('status') && $this->request->input('status') == 'expired', function (Builder $query) {
                $query->where('inventory_batchs.expiry_date', '<', DB::raw('curdate()'));
            })
            // 剩余天数
            ->when($this->request->input('expiry_diff'), function (Builder $query) {
                $query->whereRaw('DATEDIFF(cy_inventory_batchs.expiry_date, curdate()) <= ?', $this->request->input('expiry_diff'));
            })
            ->orderBy('inventory_batchs.id', 'desc');
    }

    public function map($row): array
    {
        $status = '正常';

        if (Carbon::parse($row->expiry_date)->isBefore(Carbon::today())) {
            $status = '已经过期';
        }

        $end   = Carbon::parse($row->expiry_date)->toDate();
        $start = Carbon::parse($row->expiry_date)->subDays($row->warn_days)->toDate();

        if ($row->warn_days && Carbon::today()->isBetween($start, $end)) {
            $status = '预警期内';
        }

        return [
            $row->warehouse_name,
            $row->goods_name,
            $row->specs,
            $row->manufacturer_name,
            sprintf('="%s"', $row->batch_code),
            $row->number,
            $row->unit_name,
            $row->warn_days,
            $status,
            $row->production_date,
            $row->expiry_diff,
            $row->expiry_date,
            $row->created_at
        ];
    }

    public function headings(): array
    {
        return [
            '所在仓库',
            '物品名称',
            '规格型号',
            '生产厂家',
            '批号',
            '库存数量',
            '商品单位',
            '预警天数',
            '预警状态',
            '生产日期',
            '剩余天数',
            '过期时间',
            '入库日期'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'B' => 40,
            'C' => 20,
            'D' => 25,
            'E' => 15,
            'F' => 10,
            'I' => 15,
            'J' => 12,
            'K' => 10,
        ];
    }
}
