<?php

namespace App\Exports;

use App\Models\GoodsType;
use App\Models\ImportHistory;
use App\Models\ImportHistoryRecord;
use App\Models\ImportTemplate;
use App\Models\Inventory;

// excel
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\FromCollection;

class HistoryErrorRecordsExport implements Responsable, WithHeadings, FromArray, WithStrictNullComparison, WithMapping
{
    use Exportable;

    private string $fileName;


    public function __construct(
        protected ImportHistory $importHistory
    )
    {
        $this->fileName = $this->importHistory->file_name;
    }

    /**
     * @return Collection
     */
    public function array(): array
    {
        return ImportHistoryRecord::query()
            ->where('status', ImportHistoryRecord::FAIL)
            ->where('history_id', $this->importHistory->id)
            ->pluck('row_data')->toArray();
    }

    public function headings(): array
    {
        return $this->importHistory->import_header;
    }

    public function map($row): array
    {
        // TODO: Implement map() method.
        $item = [];
        foreach ($this->importHistory->import_header as $heading) {
            $item[] = $row[$heading] ?? '';
        }

        return $item;
    }
}
