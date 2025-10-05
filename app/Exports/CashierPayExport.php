<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\CashierPay;

// excel
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class CashierPayExport implements Responsable, WithColumnWidths, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '账户流水.xlsx';

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
        return CashierPay::select('cashier_pay.*', 'customer.name as customer_name', 'customer.idcard as customer_idcard')
            ->when($this->request->input('created_at_start') && $this->request->input('created_at_end'), function ($query) {
                $query->whereBetween('cashier_pay.created_at', [
                    Carbon::parse($this->request->input('created_at_start')),
                    Carbon::parse($this->request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($this->request->input('keyword'), function ($query) {
                $query->where('customer.keyword', 'like', '%' . $this->request->input('keyword') . '%');
            })
            ->when($this->request->input('cashier_id'), function ($query) {
                $query->where('cashier_pay.cashier_id', 'like', '%' . $this->request->input('cashier_id') . '%');
            })
            ->when($this->request->input('accounts_id'), function ($query) {
                $query->where('cashier_pay.accounts_id', $this->request->input('accounts_id'));
            })
            ->when($this->request->input('remark'), function ($query) {
                $query->where('cashier_pay.remark', 'like', '%' . $this->request->input('remark') . '%');
            })
            ->when($this->request->input('user_id'), function ($query) {
                $query->where('cashier_pay.user_id', $this->request->input('user_id'));
            })
            ->leftJoin('customer', 'customer.id', '=', 'cashier_pay.customer_id')
            ->orderBy('cashier_pay.created_at', 'desc');
    }

    public function map($row): array
    {
        return [
            $row->cashier_id,
            $row->customer_name,
            sprintf('="%s"', $row->customer_idcard),
            get_accounts_name($row->accounts_id),
            $row->income,
            $row->remark,
            get_user_name($row->user_id)
        ];
    }

    public function headings(): array
    {
        return [
            '收费单号',
            '顾客姓名',
            '顾客卡号',
            '支付方式',
            '付款金额',
            '备注信息',
            '收银人员'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 38,
        ];
    }
}
