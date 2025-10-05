<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Cashier;

// excel
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class CashierExport implements Responsable, WithColumnWidths, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '收费列表.xlsx';

    /**
     * @var $request Request
     */
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * 数据库查询
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return Cashier::select('cashier.*', 'customer.name as customer_name', 'customer.idcard as customer_idcard')
            ->leftJoin('customer', 'customer.id', '=', 'cashier.customer_id')
            // 录单日期
            ->when($this->request->input('created_at_start') && $this->request->input('created_at_end'), function ($query) {
                $query->whereBetween('cashier.created_at', [
                    Carbon::parse($this->request->input('created_at_start')),
                    Carbon::parse($this->request->input('created_at_end'))->endOfDay()
                ]);
            })
            // 结单时间
            ->when($this->request->input('updated_at_start') && $this->request->input('updated_at_end'), function ($query) {
                $query->whereBetween('cashier.updated_at', [
                    Carbon::parse($this->request->input('updated_at_start')),
                    Carbon::parse($this->request->input('updated_at_end'))->endOfDay()
                ]);
            })
            ->when($this->request->input('id'), function ($query) {
                $query->where('cashier.id', $this->request->input('id'));
            })
            ->when($this->request->input('keyword'), function ($query) {
                $query->where('customer.keyword', 'like', '%' . $this->request->input('keyword') . '%');
            })
            ->when($this->request->input('keyword'), function ($query) {
                $query->where('customer.keyword', 'like', '%' . $this->request->input('keyword') . '%');
            })
            ->when($this->request->input('cashierable_type'), function ($query) {
                $query->where('cashier.cashierable_type', $this->request->input('cashierable_type'));
            })
            ->when($this->request->input('status'), function ($query) {
                $query->where('cashier.status', $this->request->input('status'));
            })
            ->when($this->request->input('operator'), function ($query) {
                $query->where('cashier.operator', $this->request->input('operator'));
            })
            ->when($this->request->input('user_id'), function ($query) {
                $query->where('cashier.user_id', $this->request->input('user_id'));
            })
            ->when($this->request->input('key'), function ($query) {
                $query->where('cashier.key', 'like', '%' . $this->request->input('key') . '%');
            })
            ->orderBy('cashier.created_at', 'desc');
    }

    public function map($row): array
    {
        $type   = config('setting.cashier.cashierable_type');
        $status = config('setting.cashier.status');

        return [
            $row->id,
            sprintf('="%s"', $row->key),
            $status[$row->status],
            $row->customer_name,
            sprintf('="%s"', $row->customer_idcard),
            $type[$row->cashierable_type],
            $row->payable,
            $row->income,
            $row->deposit,
            $row->arrearage,
            get_user_name($row->user_id),
            get_user_name($row->operator)
        ];
    }

    public function headings(): array
    {
        return [
            '收费单号',
            '收费编号',
            '业务状态',
            '顾客姓名',
            '顾客卡号',
            '业务类型',
            '应收金额',
            '实收金额',
            '余额支付',
            '欠费金额',
            '录单人员',
            '结单人员',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 38,
            'B' => 15,
            'C' => 10,
            'D' => 10,
            'E' => 13,
            'F' => 13,
            'J' => 15,
            'K' => 15,
        ];
    }
}
