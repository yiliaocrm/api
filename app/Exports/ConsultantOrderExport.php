<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\Medium;
use Illuminate\Http\Request;
use App\Models\ReceptionOrder;
use Illuminate\Database\Eloquent\Builder;

// excel
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ConsultantOrderExport implements Responsable, WithHeadings, FromQuery, WithMapping, WithColumnWidths, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '现场开单明细表.xlsx';

    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        return ReceptionOrder::query()
            ->select([
                'reception.type as reception_type',
                'reception_order.status',
                'customer.name as customer_name',
                'customer.idcard as customer_idcard',
                'reception.department_id as reception_department_id',
                'reception.consultant',
                'reception.items',
                'reception.medium_id',
                'reception_order.type',
                'reception_order.product_name',
                'reception_order.goods_name',
                'reception_order.package_name',
                'reception_order.times',
                'reception_order.unit_id',
                'reception_order.specs',
                'reception_order.price',
                'reception_order.sales_price',
                'reception_order.payable',
                'reception_order.amount',
                'reception_order.coupon',
                'reception_order.department_id',
                'reception_order.user_id',
                'reception_order.created_at'
            ])
            ->leftJoin('reception', 'reception.id', '=', 'reception_order.reception_id')
            ->leftJoin('customer', 'customer.id', '=', 'reception_order.customer_id')
            // 开单日期
            ->when($this->request->input('created_at_start') && $this->request->input('created_at_end'), function ($query) {
                $query->whereBetween('reception_order.created_at', [
                    Carbon::parse($this->request->input('created_at_start')),
                    Carbon::parse($this->request->input('created_at_end'))->endOfDay()
                ]);
            })
            // 顾客信息
            ->when($this->request->input('keyword'), function (Builder $query) {
                $query->where('customer.keyword', 'like', '%' . $this->request->input('keyword') . '%');
            })
            // 成交状态
            ->when($this->request->input('status'), function (Builder $query) {
                $query->where('reception_order.status', $this->request->input('status'));
            })
            // 接诊类型
            ->when($this->request->input('reception_type'), function (Builder $query) {
                $query->where('reception.type', $this->request->input('reception_type'));
            })
            // 现场咨询
            ->when($this->request->input('consultant'), function (Builder $query) {
                $query->where('reception.consultant', $this->request->input('consultant'));
            })
            // 咨询科室
            ->when($this->request->input('reception_department_id'), function (Builder $query) {
                $query->where('reception.department_id', $this->request->input('reception_department_id'));
            })
            // 咨询项目
            ->when($this->request->input('items'), function ($query) {
                $query->leftJoin('reception_items', 'reception.id', '=', 'reception_items.reception_id')
                    ->whereIn('reception_items.item_id', Item::query()->find($this->request->input('items'))->getAllChild()->pluck('id'));
            })
            // 媒介来源
            ->when($this->request->input('medium_id'), function ($query) {
                $query->whereIn('reception.medium_id', Medium::query()->find($this->request->input('medium_id'))->getAllChild()->pluck('id'));
            })
            ->orderBy('reception_order.created_at', 'desc');
    }

    public function map($row): array
    {
        $status = config('setting.reception_order.status');
        return [
            get_reception_type_name($row->reception_type),
            $status[$row->status],
            $row->customer_name,
            sprintf('="%s"', $row->customer_idcard),
            get_department_name($row->reception_department_id),
            get_user_name($row->consultant),
            get_items_name(json_decode($row->items, true)),
            get_medium_name($row->medium_id),
            $row->type == 'goods' ? '商品' : '项目',
            $row->product_name ?? $row->goods_name,
            $row->package_name,
            $row->times,
            get_unit_name($row->unit_id),
            $row->specs,
            $row->price,
            $row->sales_price,
            $row->payable,
            floatval($row->sales_price) ? bcmul(bcdiv($row->payable, $row->sales_price, 4), 100, 2) . '%' : '100%',
            $row->amount,
            $row->coupon,
            get_department_name($row->department_id),
            get_user_name($row->user_id),
            $row->created_at
        ];
    }

    public function headings(): array
    {
        return [
            '接诊类型',
            '成交状态',
            '顾客姓名',
            '顾客卡号',
            '咨询科室',
            '现场咨询',
            '咨询项目',
            '媒介来源',
            '类别',
            '成交项目/商品名称',
            '套餐名称',
            '次数/数量',
            '单位',
            '规格',
            '原价',
            '执行价格',
            '成交价格',
            '折扣',
            '支付金额',
            '券支付',
            '结算科室',
            '录单人员',
            '录单时间'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 10,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 20,
            'H' => 20,
            'I' => 10,
            'J' => 10,
            'K' => 30,
            'M' => 10,
            'N' => 10,
            'W' => 20
        ];
    }
}
