<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ReportFollowupRequest;
use App\Models\Followup;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReportFollowupController extends Controller
{
    /**
     * 回访情况统计
     */
    public function statistics(ReportFollowupRequest $request): JsonResponse
    {
        $rows = $request->input('rows', 10);
        $date = $request->input('date');
        $startDate = Carbon::parse($date[0])->startOfDay();
        $endDate = Carbon::parse($date[1])->endOfDay();

        // 未执行记录(按提醒人)
        $undoQuery = DB::table('followup')
            ->select([
                DB::raw("DATE_FORMAT(cy_followup.date,'%Y-%m-%d') as date"),
                DB::raw('cy_followup.followup_user as user_id'),
                DB::raw('1 as undo_count'),
                DB::raw('0 as followup_count'),
                DB::raw('0 as execute_count'),
                DB::raw('0 as create_count'),
            ])
            ->whereBetween('followup.date', [$startDate, $endDate])
            ->where('status', 1)
            ->when($request->input('department_id'), fn ($query) => $query->leftJoin('users', 'users.id', '=', 'followup.followup_user')
                ->where('users.department_id', $request->input('department_id'))
            );

        // 执行记录
        $executeQuery = DB::table('followup')
            ->select([
                DB::raw("DATE_FORMAT(cy_followup.time,'%Y-%m-%d') as date"),
                DB::raw('cy_followup.execute_user as user_id'),
                DB::raw('0 as undo_count'),
                DB::raw('0 as followup_count'),
                DB::raw('1 as execute_count'),
                DB::raw('0 as create_count'),
            ])
            ->whereBetween('followup.time', [$startDate, $endDate])
            ->when($request->input('department_id'), fn ($query) => $query->leftJoin('users', 'users.id', '=', 'followup.execute_user')
                ->where('users.department_id', $request->input('department_id'))
            );

        // 创建记录
        $createQuery = DB::table('followup')
            ->select([
                DB::raw("DATE_FORMAT(cy_followup.created_at,'%Y-%m-%d') as date"),
                DB::raw('cy_followup.user_id'),
                DB::raw('0 as undo_count'),
                DB::raw('0 as followup_count'),
                DB::raw('0 as execute_count'),
                DB::raw('1 as create_count'),
            ])
            ->whereBetween('followup.created_at', [$startDate, $endDate])
            ->when($request->input('department_id'), fn ($query) => $query->leftJoin('users', 'users.id', '=', 'followup.user_id')
                ->where('users.department_id', $request->input('department_id'))
            );

        // 提醒记录(合并查询)
        $subQuery = DB::table('followup')
            ->select([
                DB::raw("DATE_FORMAT(cy_followup.date,'%Y-%m-%d') as date"),
                DB::raw('followup_user as user_id'),
                DB::raw('0 as undo_count'),
                DB::raw('1 as followup_count'),
                DB::raw('0 as execute_count'),
                DB::raw('0 as create_count'),
            ])
            ->whereBetween('followup.date', [$startDate, $endDate])
            ->when($request->input('department_id'), fn ($query) => $query->leftJoin('users', 'users.id', '=', 'followup.followup_user')
                ->where('users.department_id', $request->input('department_id'))
            )
            ->unionAll($undoQuery)
            ->unionAll($executeQuery)
            ->unionAll($createQuery);

        $query = Followup::query()
            ->with(['user:id,name'])
            ->select([
                DB::raw('date'),
                DB::raw('user_id'),
                DB::raw('SUM(undo_count) as undo_count'),
                DB::raw('SUM(followup_count) as followup_count'),
                DB::raw('SUM(execute_count) as execute_count'),
                DB::raw('SUM(create_count) as create_count'),
            ])
            ->fromSub($subQuery, 'tabA')
            ->whereBetween('date', [$date[0], $date[1]])
            ->when($request->input('user_id'), fn ($query) => $query->where('user_id', $request->input('user_id')))
            ->groupBy('date', 'user_id')
            ->orderBy('user_id', 'desc')
            ->orderBy('date', 'desc')
            ->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }
}
