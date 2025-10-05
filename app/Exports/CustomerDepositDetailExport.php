<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\CustomerDepositDetail;
use Illuminate\Database\Eloquent\Builder;

// excel
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class CustomerDepositDetailExport implements Responsable, WithColumnWidths, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '预收账款明细表.xlsx';

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
        return CustomerDepositDetail::query()
            ->select([
                'customer.name as customer_name',
                'customer.idcard',
                'customer_deposit_details.id',
                'customer_deposit_details.cashier_id',
                'customer_deposit_details.cashierable_type',
                'customer_deposit_details.product_name',
                'customer_deposit_details.goods_name',
                'customer_deposit_details.before',
                'customer_deposit_details.balance',
                'customer_deposit_details.after',
                'customer_deposit_details.created_at',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'customer_deposit_details.customer_id')
            ->when($this->request->input('created_at_start') && $this->request->input('created_at_end'), function (Builder $query) {
                $query->whereBetween('customer_deposit_details.created_at', [
                    Carbon::parse($this->request->input('created_at_start')),
                    Carbon::parse($this->request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($this->request->input('keyword'), function (Builder $query) {
                $query->where('customer.keyword', 'like', '%' . $this->request->input('keyword') . '%');
            })
            ->when($this->request->input('cashierable_type'), function (Builder $query) {
                $query->where('customer_deposit_details.cashierable_type', $this->request->input('cashierable_type'));
            })
            ->orderBy('customer_deposit_details.id', 'desc')
            ->orderBy('customer_deposit_details.created_at', 'desc');
    }

    public function map($row): array
    {
        $type = config('setting.customer_deposit_details.cashierable_type');
        return [
            $row->id,
            $row->customer_name,
            sprintf('="%s"', $row->idcard),
            $row->cashier_id,
            $type[$row->cashierable_type],
            $row->product_name ?? $row->goods_name,
            $row->before,
            $row->balance,
            $row->after,
            $row->created_at
        ];
    }

    public function headings(): array
    {
        return [
            '编号',
            '顾客姓名',
            '顾客卡号',
            '收费单号',
            '业务类型',
            '项目/商品名称',
            '变动前',
            '变动金额',
            '变动后',
            '业务时间'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => '8',
            'B' => '13',
            'C' => '15',
            'D' => '40',
            'E' => '12',
            'F' => '35',
            'J' => '25'
        ];
    }
}
