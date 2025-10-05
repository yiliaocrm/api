<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Medium;
use App\Models\ErkaiDetail;

// excel
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ErkaiDetailExport implements Responsable, WithColumnWidths, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '二开零购明细表.xlsx';

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
        return ErkaiDetail::query()
            ->select([
                'customer.name as customer_name',
                'customer.idcard as customer_idcard',
                'erkai.medium_id',
                'erkai_detail.goods_name',
                'erkai_detail.product_name',
                'erkai_detail.price',
                'erkai_detail.payable',
                'erkai_detail.amount',
                'erkai_detail.coupon',
                'erkai_detail.department_id',
                'erkai_detail.salesman',
                'erkai_detail.user_id',
                'erkai_detail.created_at',
            ])
            ->leftJoin('erkai', 'erkai.id', '=', 'erkai_detail.erkai_id')
            ->leftJoin('customer', 'customer.id', '=', 'erkai_detail.customer_id')
            // 成交状态
            ->where('erkai_detail.status', 3)
            ->when($this->request->input('created_at_start') && $this->request->input('created_at_end'), function (Builder $query) {
                $query->whereBetween('erkai_detail.created_at', [
                    Carbon::parse($this->request->input('created_at_start')),
                    Carbon::parse($this->request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($this->request->input('keyword'), function (Builder $query) {
                $query->where('keyword', 'like', '%' . $this->request->input('keyword') . '%');
            })
            ->when($this->request->input('name'), function (Builder $query) {
                $query->where(function ($query) {
                    $query->where('erkai_detail.product_name', 'like', '%' . $this->request->input('name') . '%')->orWhere('erkai_detail.goods_name', 'like', '%' . $this->request->input('name') . '%');
                });
            })
            ->when($this->request->input('department_id'), function (Builder $query) {
                $query->where('erkai_detail.department_id', $this->request->input('department_id'));
            })
            // 媒介来源
            ->when($this->request->input('medium_id'), function ($query) {
                $query->whereIn('erkai.medium_id', Medium::query()->find($this->request->input('medium_id'))->getAllChild()->pluck('id'));
            })
            ->when($this->request->input('user_id'), function (Builder $query) {
                $query->where('erkai_detail.user_id', $this->request->input('user_id'));
            })
            ->orderByDesc('erkai_detail.created_at');
    }

    public function headings(): array
    {
        return [
            '顾客姓名',
            '顾客卡号',
            '媒介来源',
            '项目/商品名称',
            '原价',
            '成交价格',
            '支付金额',
            '券支付',
            '结算科室',
            '销售人员',
            '录单人员',
            '收费时间'
        ];
    }

    public function map($row): array
    {
        return [
            $row->customer_name,
            sprintf('="%s"', $row->customer_idcard),
            get_medium_name($row->medium_id),
            $row->goods_name ?? $row->product_name,
            $row->price,
            $row->payable,
            $row->amount,
            $row->coupon,
            get_department_name($row->department_id),
            formatter_salesman($row->salesman),
            get_user_name($row->user_id),
            $row->created_at,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 15,
            'C' => 20,
            'D' => 40,
            'E' => 10,
            'F' => 10,
            'J' => 15,
            'K' => 15,
        ];
    }
}
