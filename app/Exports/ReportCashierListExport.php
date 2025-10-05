<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Cashier;
use App\Models\Accounts;
use Illuminate\Http\Request;

// excel
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ReportCashierListExport implements Responsable, WithHeadings, FromQuery, WithMapping, WithColumnWidths, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '收费记录表.xlsx';

    /**
     * @var $request Request
     */
    protected Request $request;

    /**
     * 收费账户
     * @var \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    protected $accounts;

    public function __construct(Request $request)
    {
        $this->request  = $request;
        $this->accounts = Accounts::query()->orderBy('id', 'asc')->get();
    }

    public function query()
    {
        return Cashier::query()
            ->with('pay')
            ->select('cashier.*', 'customer.name as customer_name', 'customer.idcard as customer_idcard')
            ->leftJoin('customer', 'customer.id', '=', 'cashier.customer_id')
            ->where('status', 2)
            ->when($this->request->input('created_at_start') && $this->request->input('created_at_end'), function ($query) {
                return $query->whereBetween('cashier.created_at', [
                    Carbon::parse($this->request->input('created_at_start')),
                    Carbon::parse($this->request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($this->request->input('customer_keyword'), function ($query) {
                return $query->where('customer.keyword', 'like', '%' . $this->request->input('customer_keyword') . '%');
            })
            ->when($this->request->input('user_id'), function ($query) {
                return $query->where('cashier.user_id', $this->request->input('user_id'));
            })
            ->when($this->request->input('id'), function ($query) {
                return $query->where(function ($query) {
                    $query->where('cashier.id', $this->request->input('id'))->orWhere('cashier.key', 'like', '%' . $this->request->input('id') . '%');
                });
            })
            ->when($this->request->input('updated_at_start') && $this->request->input('updated_at_end'), function ($query) {
                return $query->whereBetween('cashier.updated_at', [
                    Carbon::parse($this->request->input('updated_at_start')),
                    Carbon::parse($this->request->input('updated_at_end'))->endOfDay(),
                ]);
            })
            ->when($this->request->input('operator'), function ($query) {
                return $query->where('cashier.operator', $this->request->input('operator'));
            })
            ->when($this->request->input('cashierable_type'), function ($query) {
                return $query->where('cashier.cashierable_type', $this->request->input('cashierable_type'));
            })
            ->orderBy('cashier.created_at', 'desc');
    }

    public function map($row): array
    {
        $type     = config('setting.cashier.cashierable_type');
        $accounts = [];

        $this->accounts->each(function ($v) use ($row, &$accounts) {
            array_push($accounts, $row->pay->where('accounts_id', $v->id)->sum('income'));
        });

        return [
            $row->id,
            sprintf('="%s"', $row->key),
            $row->customer_name,
            sprintf('="%s"', $row->customer_idcard),
            get_user_name($row->user_id),
            get_user_name($row->operator),
            $row->cashierable_type == 'App\\Models\\Recharge' ? 1 : count($row->detail),
            $type[$row->cashierable_type],
            $row->payable,
            $row->income,
            ...$accounts,
            $row->created_at,
            $row->updated_at,
        ];
    }

    public function headings(): array
    {
        $accounts_name = $this->accounts->pluck('name')->toArray();
        return [
            '收费单号',
            '收费编号',
            '顾客姓名',
            '顾客卡号',
            '录单人员',
            '收费人员',
            '业务单数',
            '业务类型',
            '应收金额',
            '实收金额',
            ...$accounts_name,
            '录单时间',
            '收费时间'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40,
            'B' => 20,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'H' => 10,
        ];
    }
}
