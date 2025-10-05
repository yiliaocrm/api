<?php

namespace App\Http\Controllers\Web;

use App\Models\Accounts;
use App\Models\Department;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Database\Query\JoinClause;
use App\Http\Requests\Web\ReportCashierRequest;

class ReportCashierController extends Controller
{
    /**
     * 收费汇总表
     * @param ReportCashierRequest $request
     * @return JsonResponse
     */
    public function collect(ReportCashierRequest $request): JsonResponse
    {
        $accounts   = Accounts::all();
        $created_at = $request->input('created_at');

        // Subquery 1
        $subQuery1 = DB::table('cashier_detail')
            ->select([
                DB::raw('date(created_at) as date'),
                DB::raw('count(DISTINCT cashier_id) as number'),
                DB::raw('SUM(income) as income'),
                DB::raw('SUM(deposit) as deposit'),
                DB::raw('SUM(CASE WHEN product_id <> 1 THEN income + deposit ELSE 0 END) as turnover'),
                DB::raw('SUM(arrearage) as arrearage'),
                DB::raw('SUM(CASE WHEN cashierable_type = "App\\\\Models\\\\CashierArrearage" THEN income ELSE 0 END) as repayment'),
                DB::raw('SUM(CASE WHEN cashierable_type = "App\\\\Models\\\\CashierRefund" THEN ABS(income) ELSE 0 END) as refund')
            ])
            ->whereBetween('created_at', [
                Carbon::parse($created_at[0])->startOfDay(),
                Carbon::parse($created_at[1])->endOfDay(),
            ])
            ->groupBy(DB::raw('date(created_at)'));

        // Subquery 2
        $subQuery2 = DB::table('cashier_pay')
            ->select([DB::raw('DATE(created_at) as date')])
            ->whereBetween('created_at', [
                Carbon::parse($created_at[0])->startOfDay(),
                Carbon::parse($created_at[1])->endOfDay(),
            ])
            ->groupBy(DB::raw('DATE(created_at)'));

        // 循环所有收费账户
        foreach ($accounts as $account) {
            $subQuery2->addSelect(DB::raw("COALESCE(SUM(CASE WHEN accounts_id = {$account->id} THEN income ELSE 0 END), 0) as pay{$account->id}"));
        }

        // Main query with left join
        $results = DB::query()
            ->select([
                'cy_d.date',
                'cy_d.number',
                'cy_d.income',
                'cy_d.deposit',
                'cy_d.turnover',
                'cy_d.arrearage',
                'cy_d.repayment',
                'cy_d.refund',
            ])
            ->selectRaw(implode(',', array_map(function ($account) {
                return "COALESCE(cy_cy_p.pay{$account->id}, 0) as pay{$account->id}";
            }, $accounts->all())))
            ->fromSub($subQuery1, 'cy_d')
            ->leftJoinSub($subQuery2, 'cy_p', 'cy_d.date', '=', 'cy_p.date')
            ->orderBy('cy_d.date', 'desc')
            ->paginate($request->input('rows', 10));

        return response_success([
            'rows'  => $results->items(),
            'total' => $results->total()
        ]);
    }

    /**
     * 科室营业汇总表
     * @param ReportCashierRequest $request
     * @return JsonResponse
     */
    public function department(ReportCashierRequest $request): JsonResponse
    {
        $user_id    = $request->input('user_id');
        $created_at = $request->input('created_at');

        // 获取带前缀的表别名
        $cdAlias = DB::connection()->getQueryGrammar()->wrapTable('cd');

        $data = Department::query()
            ->select([
                'department.id as department_id',
                'department.name as department_name',
                DB::raw("COALESCE(SUM({$cdAlias}.income), 0) as income"),
                DB::raw("COALESCE(SUM(CASE WHEN {$cdAlias}.product_id <> 1 THEN {$cdAlias}.income + {$cdAlias}.deposit ELSE 0 END), 0) as turnover")
            ])
            ->leftJoin('cashier_detail as cd', function (JoinClause $join) use ($created_at, $user_id) {
                $join->on('department.id', '=', 'cd.department_id')
                    ->whereBetween('cd.created_at', [
                        Carbon::parse($created_at[0])->startOfDay(),
                        Carbon::parse($created_at[1])->endOfDay()
                    ])
                    ->when($user_id, function ($query) use ($user_id) {
                        $query->where('cd.user_id', '=', $user_id);
                    });
            })
            ->where('department.primary', 1)
            ->groupBy(['department.id', 'department.name'])
            ->get();

        $footer = [
            [
                'department_name' => '合计',
                'income'          => $data->sum('income'),
                'turnover'        => $data->sum('turnover'),
            ]
        ];

        return response_success([
            'rows'   => $data,
            'footer' => $footer
        ]);
    }
}
