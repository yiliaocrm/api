<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

// excel
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class AppointmentExport implements Responsable, WithEvents, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '预约记录表.xlsx';

    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        return Appointment::query()
            ->select('appointments.*', 'customer.name as customer_name')
            ->leftJoin('customer', 'customer.id', '=', 'appointments.customer_id')
            ->when($this->request->input('date_start') && $this->request->input('date_end'), function (Builder $query) {
                $query->whereBetween('appointments.date', [
                    $this->request->input('date_start'),
                    $this->request->input('date_end')
                ]);
            })
            ->when($this->request->input('created_at_start') && $this->request->input('created_at_end'), function (Builder $query) {
                $query->whereBetween('appointments.created_at', [
                    Carbon::parse($this->request->input('created_at_start')),
                    Carbon::parse($this->request->input('created_at_end'))->endOfDay()
                ]);
            })
            // 顾客信息
            ->when($this->request->input('customer_keyword'), function ($query) {
                $query->where('customer.keyword', 'like', '%' . $this->request->input('customer_keyword') . '%');
            })
            // 项目名称
            ->when($this->request->input('items_name'), function ($query) {
                $query->where('appointments.items_name', 'like', '%' . $this->request->input('items_name') . '%');
            })
            // 预约状态
            ->when($this->request->input('status') !== null, function ($query) {
                $query->where('appointments.status', $this->request->input('status'));
            })
            ->when($this->request->input('department_id'), function ($query) {
                $query->where('appointments.department_id', $this->request->input('department_id'));
            })
            ->when($this->request->input('doctor_id'), function ($query) {
                $query->where('appointments.doctor_id', $this->request->input('doctor_id'));
            });
    }

    public function map($row): array
    {
        return [
            $row->status,
            $row->customer_name,
            $row->date,
            $row->start,
            $row->end,
            $row->duration,
            $row->department_id,
            $row->doctor_id,
            $row->items_name,
            $row->remark,
            $row->user_id,
            $row->created_at,
        ];
    }

    public function headings(): array
    {
        return [
            '预约状态',
            '顾客姓名',
            '预约日期',
            '预约时间',
            '结束时间',
            '持续时间(分)',
            '预约科室',
            '预约医生',
            '预约项目',
            '预约备注',
            '录单人员',
            '创建时间',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getColumnDimension('B')->setAutoSize(false)->setWidth(15);
            }
        ];
    }
}
