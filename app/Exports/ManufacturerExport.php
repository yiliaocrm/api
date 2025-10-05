<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Manufacturer;
use Illuminate\Http\Request;

// excel
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ManufacturerExport implements Responsable, WithColumnWidths, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '生产厂商名单.xlsx';

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
        return Manufacturer::query()
            ->when($this->request->input('created_at_start') && $this->request->input('created_at_end'), function ($query) {
                $query->whereBetween('created_at', [
                    Carbon::parse($this->request->input('created_at_start')),
                    Carbon::parse($this->request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($this->request->input('keyword'), function ($query) {
                $query->where('keyword', 'like', "%{$this->request->input('keyword')}%");
            });
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->short_name,
            $row->remark,
            $row->created_at,
            $row->updated_at
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            '生产厂商名称',
            '生产厂商简称',
            '备注信息',
            '创建时间',
            '更新时间'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 30,
            'C' => 30,
        ];
    }
}
