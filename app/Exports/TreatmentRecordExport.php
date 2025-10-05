<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Treatment;
use App\Models\ProductType;

// excel
use Illuminate\Http\Request;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class TreatmentRecordExport implements Responsable, WithEvents, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '治疗记录.xlsx';

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
        return Treatment::query()
            ->select([
                'treatment.*',
                'customer.name as customer_name',
                'customer.idcard as customer_idcard',
                'product_type.name as product_type_name'
            ])
            ->leftJoin('customer', 'customer.id', '=', 'treatment.customer_id')
            ->leftJoin('product', 'treatment.product_id', '=', 'product.id')
            ->leftJoin('product_type', 'product.type_id', '=', 'product_type.id')
            ->when($this->request->input('created_at_start') && $this->request->input('created_at_end'), function ($query) {
                $query->whereBetween('treatment.created_at', [
                    Carbon::parse($this->request->input('created_at_start')),
                    Carbon::parse($this->request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($this->request->input('keyword'), function ($query) {
                $query->where('customer.keyword', 'like', '%' . $this->request->input('keyword') . '%');
            })
            ->when($this->request->input('product_name'), function ($query) {
                $query->where('treatment.product_name', 'like', '%' . $this->request->input('product_name') . '%');
            })
            ->when($this->request->input('remark'), function ($query) {
                $query->where('treatment.remark', 'like', '%' . $this->request->input('remark') . '%');
            })
            ->when($this->request->input('user_id'), function ($query) {
                $query->where('treatment.user_id', $this->request->input('user_id'));
            })
            ->when($this->request->input('participants'), function ($query) {
                $query->leftJoin('treatment_participants', 'treatment.id', '=', 'treatment_participants.treatment_id')
                    ->where('treatment_participants.user_id', $this->request->input('participants'));
            })
            ->when($this->request->input('department_id'), function ($query) {
                $query->where('treatment.department_id', $this->request->input('department_id'));
            })
            ->when($this->request->input('package_name'), function ($query) {
                $query->where('treatment.package_name', 'like', '%' . $this->request->input('package_name') . '%');
            })
            ->when(request('product_type') && request('product_type') != 1, function ($query) {
                $query->whereIn('product.type_id', ProductType::find(request('product_type'))->getAllChild()->pluck('id'));
            })
            ->orderBy('treatment.created_at', 'desc');
    }

    public function map($row): array
    {
        $status = config('setting.treatment.status');
        return [
            $status[$row->status],
            $row->customer_name,
            sprintf('="%s"', $row->customer_idcard),
            $row->product_type_name,
            $row->product_name,
            $row->package_name,
            $row->times,
            $row->price,
            $row->arrearage,
            get_department_name($row->department_id),
            $row->remark,
            formatter_salesman($row->participants),
            get_user_name($row->user_id),
            $row->created_at,
        ];
    }


    public function headings(): array
    {
        return [
            '状态',
            '顾客姓名',
            '顾客卡号',
            '项目分类',
            '项目名称',
            '套餐名称',
            '扣划次数',
            '扣划金额',
            '欠款金额',
            '执行科室',
            '扣划备注',
            '配台人员',
            '划扣人员',
            '扣划时间',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getColumnDimension('A')->setAutoSize(false)->setWidth(10);
                $event->sheet->getColumnDimension('C')->setAutoSize(false)->setWidth(15);
                $event->sheet->getColumnDimension('D')->setAutoSize(false)->setWidth(15);
                $event->sheet->getColumnDimension('E')->setAutoSize(false)->setWidth(50);
                $event->sheet->getColumnDimension('M')->setAutoSize(false)->setWidth(20);
            }
        ];
    }
}
