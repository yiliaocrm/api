<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\Medium;
use App\Models\Consultant;
use Illuminate\Http\Request;

// excel
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ConsultantDetailExport implements Responsable, WithColumnWidths, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '现场咨询明细表.xlsx';

    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        return Consultant::query()
            ->select('reception.*', 'customer.name as customer_name', 'customer.idcard as customer_idcard')
            ->leftJoin('customer', 'customer.id', '=', 'reception.customer_id')
            // 咨询日期
            ->when($this->request->input('created_at_start') && $this->request->input('created_at_end'), function ($query) {
                $query->whereBetween('reception.created_at', [
                    Carbon::parse($this->request->input('created_at_start')),
                    Carbon::parse($this->request->input('created_at_end'))->endOfDay()
                ]);
            })
            // 成交状态
            ->when($this->request->input('status'), function ($query) {
                $query->where('reception.status', $this->request->input('status'));
            })
            // 接诊类型
            ->when($this->request->input('type'), function ($query) {
                $query->where('reception.type', $this->request->input('type'));
            })
            // 现场咨询
            ->when($this->request->input('consultant'), function ($query) {
                $query->where('reception.consultant', $this->request->input('consultant'));
            })
            // 咨询科室
            ->when($this->request->input('department_id'), function ($query) {
                $query->where('reception.department_id', $this->request->input('department_id'));
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
            ->orderBy('reception.created_at', 'desc');
    }

    public function map($row): array
    {
        $status = config('setting.reception.status');

        return [
            $status[$row->status],
            get_reception_type_name($row->type),
            $row->receptioned ? '是' : '否',
            $row->customer_name,
            sprintf('="%s"', $row->customer_idcard),
            get_department_name($row->department_id),
            get_items_name($row->items),
            get_failure_name($row->failure_id),
            $row->remark,
            get_medium_name($row->medium_id),
            get_user_name($row->consultant),
            get_user_name($row->ek_user),
            get_user_name($row->doctor),
            get_user_name($row->reception),
            get_user_name($row->user_id),
            $row->created_at
        ];
    }

    public function headings(): array
    {
        return [
            '成交状态',
            '接诊类型',
            '是否接待',
            '顾客姓名',
            '顾客卡号',
            '咨询科室',
            '咨询项目',
            '未成交原因',
            '咨询备注',
            '媒介来源',
            '现场咨询',
            '二开人员',
            '助诊医生',
            '分诊接待',
            '录单人员',
            '录单时间'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => '10',
            'B' => '10',
            'D' => '15',
            'E' => '15',
            'F' => '15',
            'G' => '20',
            'H' => '20',
            'I' => '50',
            'M' => '20',
            'N' => '20',
        ];
    }
}
