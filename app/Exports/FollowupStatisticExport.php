<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Followup;
use Illuminate\Http\Request;

// excel
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class FollowupStatisticExport implements Responsable, WithColumnWidths, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '回访情况统计表.xlsx';

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
        $startDate = Carbon::parse($this->request->input('date_start'))->startOfDay();
        $endDate   = Carbon::parse($this->request->input('date_end'))->endOfDay();

        // 未执行记录(按提醒人)
        $undoQuery = DB::table('followup')
            ->select([
                DB::raw("DATE_FORMAT(cy_followup.date,'%Y-%m-%d') as date"),
                DB::raw('cy_followup.followup_user as user_id'),
                DB::raw('1 as undo_count'),
                DB::raw('0 as followup_count'),
                DB::raw('0 as execute_count'),
                DB::raw('0 as create_count')
            ])
            ->whereBetween('followup.date', [$startDate, $endDate])
            ->where('status', 1)
            ->when($this->request->input('department_id'), fn($query) => $query->leftJoin('users', 'users.id', '=', 'followup.followup_user')
                ->where('users.department_id', $this->request->input('department_id'))
            );

        // 执行记录
        $executeQuery = DB::table('followup')
            ->select([
                DB::raw("DATE_FORMAT(cy_followup.time,'%Y-%m-%d') as date"),
                DB::raw('cy_followup.execute_user as user_id'),
                DB::raw('0 as undo_count'),
                DB::raw('0 as followup_count'),
                DB::raw('1 as execute_count'),
                DB::raw('0 as create_count')
            ])
            ->whereBetween('followup.time', [$startDate, $endDate])
            ->when($this->request->input('department_id'), fn($query) => $query->leftJoin('users', 'users.id', '=', 'followup.execute_user')
                ->where('users.department_id', $this->request->input('department_id'))
            );

        // 创建记录
        $createQuery = DB::table('followup')
            ->select([
                DB::raw("DATE_FORMAT(cy_followup.created_at,'%Y-%m-%d') as date"),
                DB::raw('cy_followup.user_id'),
                DB::raw('0 as undo_count'),
                DB::raw('0 as followup_count'),
                DB::raw('0 as execute_count'),
                DB::raw('1 as create_count')
            ])
            ->whereBetween('followup.created_at', [$startDate, $endDate])
            ->when($this->request->input('department_id'), fn($query) => $query->leftJoin('users', 'users.id', '=', 'followup.user_id')
                ->where('users.department_id', $this->request->input('department_id'))
            );

        // 合并查询
        $subQuery = DB::table('followup')
            ->select([
                DB::raw("DATE_FORMAT(cy_followup.date,'%Y-%m-%d') as date"),
                DB::raw('cy_followup.followup_user as user_id'),
                DB::raw('0 as undo_count'),
                DB::raw('1 as followup_count'),
                DB::raw('0 as execute_count'),
                DB::raw('0 as create_count')
            ])
            ->whereBetween('followup.date', [$startDate, $endDate])
            ->when($this->request->input('department_id'), fn($query) => $query->leftJoin('users', 'users.id', '=', 'followup.followup_user')
                ->where('users.department_id', $this->request->input('department_id'))
            )
            ->unionAll($undoQuery)
            ->unionAll($executeQuery)
            ->unionAll($createQuery);

        return Followup::query()
            ->select([
                DB::raw('date'),
                DB::raw('user_id'),
                DB::raw('sum(undo_count) as undo_count'),
                DB::raw('SUM(followup_count) as followup_count'),
                DB::raw('SUM(execute_count) as execute_count'),
                DB::raw('SUM(create_count) as create_count')
            ])
            ->fromSub($subQuery, 'tabA')
            ->whereBetween('date', [$this->request->input('date_start'), $this->request->input('date_end')])
            ->when($this->request->input('user_id'), fn($query) => $query->where('user_id', $this->request->input('user_id')))
            ->groupBy('date', 'user_id')
            ->orderBy('date', 'desc');
    }

    public function map($row): array
    {
        return [
            get_user_name($row->user_id),
            $row->date,
            $row->undo_count,
            $row->followup_count,
            $row->execute_count,
            $row->create_count
        ];
    }

    public function headings(): array
    {
        return [
            '员工姓名',
            '营业日期',
            '未执行回访数量',
            '计划回访数量',
            '完成回访数量',
            '创建回访数量'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 13,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 20,
        ];
    }
}
