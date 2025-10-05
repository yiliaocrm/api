<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\CashierDetail;

// excel
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class CashierDetailExport implements Responsable, WithColumnWidths, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '营收明细.xlsx';

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
        return CashierDetail::query()
            ->select('cashier_detail.*', 'customer.name as customer_name', 'customer.idcard as customer_idcard')
            ->leftJoin('customer', 'customer.id', '=', 'cashier_detail.customer_id')
            ->when($this->request->input('created_at_start') && $this->request->input('created_at_end'), function ($query) {
                $query->whereBetween('cashier_detail.created_at', [
                    Carbon::parse($this->request->input('created_at_start')),
                    Carbon::parse($this->request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($this->request->input('customer_keyword'), function ($query) {
                return $query->where('customer.keyword', 'like', '%' . $this->request->input('customer_keyword') . '%');
            })
            ->when($this->request->input('product_name'), function ($query) {
                return $query->where(function ($query) {
                    $query->where('cashier_detail.product_name', 'like', '%' . $this->request->input('product_name') . '%')
                        ->orWhere('cashier_detail.goods_name', 'like', '%' . $this->request->input('product_name') . '%');
                });
            })
            ->when($this->request->input('package_name'), function ($query) {
                return $query->where('cashier_detail.package_name', 'like', '%' . $this->request->input('product_name') . '%');
            })
            ->when($this->request->input('cashierable_type'), function ($query) {
                return $query->where('cashier_detail.cashierable_type', $this->request->input('cashierable_type'));
            })
            ->when($this->request->input('department_id'), function ($query) {
                return $query->where('cashier_detail.department_id', $this->request->input('department_id'));
            })
            ->when($this->request->input('user_id'), function ($query) {
                return $query->where('cashier_detail.user_id', $this->request->input('user_id'));
            })
            // 收费单号
            ->when($this->request->input('cashier_id'), function ($query) {
                return $query->where('cashier_detail.cashier_id', 'like', '%' . $this->request->input('cashier_id') . '%');
            })
            ->orderBy('cashier_detail.created_at', 'desc');
    }

    public function map($row): array
    {
        $type = config('setting.cashier.cashierable_type');
        return [
            $row->id,
            $type[$row->cashierable_type],
            $row->customer_name,
            sprintf('="%s"', $row->customer_idcard),
            $row->product_name,
            $row->goods_name,
            $row->package_name,
            $row->times,
            get_unit_name($row->unit_id),
            $row->specs,
            $row->payable,
            $row->income,
            $row->deposit,
            $row->arrearage,
//            $model->salesman,
            get_department_name($row->department_id),
            get_user_name($row->user_id),
            $row->created_at
        ];
    }

    public function headings(): array
    {
        return [
            '收费单号',
            '业务类型',
            '顾客姓名',
            '顾客卡号',
            '项目名称',
            '物品名称',
            '套餐名称',
            '次数/数量',
            '单位',
            '规格',
            '应收金额',
            '实收金额',
            '余额支付',
            '欠款金额',
//            '销售人员',
            '结算科室',
            '收银员',
            '收款时间',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 38,
            'B' => 15,
            'E' => 30,
            'F' => 30,
            'G' => 30,
        ];
    }
}
