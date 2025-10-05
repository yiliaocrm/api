<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\CouponDetail;

// excel
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class CouponDetailExport implements Responsable, WithColumnWidths, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '领券记录.xlsx';


    public function query()
    {
        return CouponDetail::query()
            ->select([
                'coupon_details.status',
                'customer.name as customer_name',
                'customer.idcard as customer_idcard',
                'coupon_details.coupon_name',
                'coupon_details.number',
                'coupon_details.coupon_value',
                'coupon_details.balance',
                'coupon_details.sales_price',
                'coupon_details.rate',
                'coupon_details.integrals',
                'coupon_details.expire_time',
                'coupon_details.created_at',
                'coupon_details.remark',
                'coupon_details.create_user_id',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'coupon_details.customer_id')
            ->when(request('created_at_start') && request('created_at_end'), function (Builder $query) {
                $query->whereBetween('coupon_details.created_at', [
                    Carbon::parse(request('created_at_start')),
                    Carbon::parse(request('created_at_end'))->endOfDay()
                ]);
            })
            ->when(request('keyword'), function (Builder $query) {
                $query->where('customer.keyword', 'like', '%' . request('keyword') . '%');
            })
            ->when(request('number'), function (Builder $query) {
                $query->where('coupon_details.number', 'like', '%' . request('number') . '%');
            })
            ->when(request('create_user_id'), function (Builder $query) {
                $query->where('coupon_details.create_user_id', request('create_user_id'));
            })
            ->when(request('status'), function (Builder $query) {
                $query->where('coupon_details.status', request('status'));
            });
    }

    public function headings(): array
    {
        return [
            '状态',
            '顾客姓名',
            '顾客卡号',
            '卡券名称',
            '卡券编号',
            '卡券面值',
            '卡券余额',
            '支付金额',
            '充赠比例',
            '扣除积分',
            '过期时间',
            '发券时间',
            '备注信息',
            '发券人员'
        ];
    }

    public function map($row): array
    {
        $setting = config('setting.coupon_details.status');
        return [
            $setting[$row->status],
            $row->customer_name,
            sprintf('="%s"', $row->customer_idcard),
            $row->coupon_name,
            sprintf('="%s"', $row->number),
            $row->coupon_value,
            $row->balance,
            $row->sales_price,
            $row->rate,
            $row->integrals,
            $row->expire_time,
            $row->created_at,
            $row->remark,
            get_user_name($row->create_user_id),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 15,
            'C' => 20,
            'D' => 40,
            'E' => 20,
            'F' => 10,
            'J' => 10,
            'K' => 15,
        ];
    }
}
