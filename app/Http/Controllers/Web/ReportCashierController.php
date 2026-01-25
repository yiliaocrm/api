<?php

namespace App\Http\Controllers\Web;

use App\Models\Cashier;
use App\Models\Accounts;
use App\Models\Department;
use App\Models\CustomerDepositDetail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ReportCashierRequest;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

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

    /**
     * 预收账款表
     * @param ReportCashierRequest $request
     * @return JsonResponse
     */
    public function depositReceived(ReportCashierRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $prefix  = DB::getTablePrefix();
        $keyword = $request->input('keyword');

        // 期初
        $before = CustomerDepositDetail::query()
            ->select('before')
            ->from('customer_deposit_details', 'a')
            ->where('a.customer_id', '=', DB::raw("{$prefix}c.customer_id"))
            ->whereBetween('a.created_at', [
                Carbon::parse($request->input('date.0'))->startOfDay(),
                Carbon::parse($request->input('date.1'))->endOfDay()
            ])
            ->orderBy('a.id')
            ->limit(1);

        // 期末
        $after = CustomerDepositDetail::query()
            ->select('after')
            ->from('customer_deposit_details', 'b')
            ->where('b.customer_id', '=', DB::raw("{$prefix}c.customer_id"))
            ->whereBetween('b.created_at', [
                Carbon::parse($request->input('date.0'))->startOfDay(),
                Carbon::parse($request->input('date.1'))->endOfDay()
            ])
            ->orderByDesc('b.id')
            ->limit(1);

        $builder = CustomerDepositDetail::query()
            ->select([
                'customer.id as customer_id',
                'customer.name as customer_name',
                'customer.idcard',
            ])
            ->selectRaw("any_value ( {$prefix}c.id ) AS id")
            ->selectSub($before, 'before')
            ->selectSub($after, 'after')
            ->addSelect(DB::raw("SUM( CASE WHEN {$prefix}c.balance > 0 THEN {$prefix}c.balance ELSE 0 END ) AS 'recharge'"))
            ->addSelect(DB::raw("ABS(SUM( CASE WHEN {$prefix}c.balance < 0 AND {$prefix}c.cashierable_type <> 'App\\\\Models\\\\CashierRefund' THEN {$prefix}c.balance ELSE 0 END )) AS 'used'"))
            ->addSelect(DB::raw("ABS(SUM( CASE WHEN {$prefix}c.balance < 0 AND {$prefix}c.cashierable_type = 'App\\\\Models\\\\CashierRefund' THEN {$prefix}c.balance ELSE 0 END )) AS 'refund'"))
            ->from('customer_deposit_details', 'c')
            ->leftJoin('customer', 'customer.id', '=', 'c.customer_id')
            ->whereBetween('c.created_at', [
                Carbon::parse($request->input('date.0'))->startOfDay(),
                Carbon::parse($request->input('date.1'))->endOfDay()
            ])
            ->when($keyword, fn(Builder $query) => $query->whereLike('customer.name', "%{$keyword}%"))
            ->groupBy('c.customer_id')
            ->orderByDesc('id');

        // 查询
        $query = $builder->clone()->paginate($rows);

        // 合计
        $items = collect($query->items());
        $table = DB::query()->fromSub($builder->clone(), 'sub');

        $footer = [
            [
                'idcard'   => '页小计:',
                'before'   => floatval($items->sum('before')),
                'after'    => floatval($items->sum('after')),
                'used'     => floatval($items->sum('used')),
                'refund'   => floatval($items->sum('refund')),
                'recharge' => floatval($items->sum('recharge')),
            ],
            [
                'idcard'   => '总合计:',
                'before'   => floatval($table->sum('before')),
                'after'    => floatval($table->sum('after')),
                'used'     => floatval($table->sum('used')),
                'refund'   => floatval($table->sum('refund')),
                'recharge' => floatval($table->sum('recharge')),
            ]
        ];

        return response_success([
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ]);
    }

    /**
     * 预收款项变动明细表
     * @param ReportCashierRequest $request
     * @return JsonResponse
     */
    public function depositReceivedDetail(ReportCashierRequest $request): JsonResponse
    {
        $sort             = $request->input('sort', 'customer_deposit_details.created_at');
        $rows             = $request->input('rows', 10);
        $order            = $request->input('order', 'desc');
        $keyword          = $request->input('keyword');
        $cashierable_type = $request->input('cashierable_type');

        $query = CustomerDepositDetail::query()
            ->select([
                'customer.id as customer_id',
                'customer.name as customer_name',
                'customer.idcard',
                'customer_deposit_details.id',
                'customer_deposit_details.cashier_id',
                'customer_deposit_details.cashierable_type',
                'customer_deposit_details.product_name',
                'customer_deposit_details.goods_name',
                'customer_deposit_details.before',
                'customer_deposit_details.balance',
                'customer_deposit_details.after',
                'customer_deposit_details.created_at',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'customer_deposit_details.customer_id')
            ->whereBetween('customer_deposit_details.created_at', [
                Carbon::parse($request->input('date.0'))->startOfDay(),
                Carbon::parse($request->input('date.1'))->endOfDay()
            ])
            ->when($keyword, fn(Builder $query) => $query->whereLike('customer.name', "%{$keyword}%"))
            ->when($cashierable_type, fn(Builder $query) => $query->where('customer_deposit_details.cashierable_type', $cashierable_type))
            ->orderBy('customer_deposit_details.id', 'desc')
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 收费明细表
     * @param ReportCashierRequest $request
     * @return JsonResponse
     */
    public function list(ReportCashierRequest $request): JsonResponse
    {
        $sort      = $request->input('sort', 'created_at');
        $order     = $request->input('order', 'desc');
        $rows      = $request->input('rows', 10);
        $keyword   = $request->input('keyword');
        $createdAt = $request->input('created_at');

        // 获取所有支付方式
        $accounts = Accounts::all()->keyBy('id');

        $builder = Cashier::query()
            ->with([
                'pay',
                'user:id,name',
                'operatorUser:id,name',
                'customer:id,name,sex,idcard'
            ])
            ->select([
                'cashier.*'
            ])
            ->leftJoin('customer', 'customer.id', '=', 'cashier.customer_id')
            ->where('status', 2)
            ->whereBetween('cashier.created_at', [
                Carbon::parse($createdAt[0])->startOfDay(),
                Carbon::parse($createdAt[1])->endOfDay()
            ])
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', '%' . $keyword . '%'))
            ->queryConditions('ReportCashierList');

        // 查询
        $query = $builder->clone()->orderBy("cashier.{$sort}", $order)->paginate($rows);

        // 合计
        $items = collect($query->items());
        $table = DB::query()->fromSub($builder->clone(), 'sub');

        // 构建支付方式聚合查询（用于页小计和总合计）
        $cashierIds = $items->pluck('id');
        $payQuery   = DB::table('cashier_pay as cp')
            ->join('cashier as c', 'c.id', '=', 'cp.cashier_id')
            ->where('c.status', 2)
            ->whereBetween('c.created_at', [
                Carbon::parse($createdAt[0])->startOfDay(),
                Carbon::parse($createdAt[1])->endOfDay()
            ]);

        // 计算各支付方式的页小计和总合计
        $payPageSums  = [];
        $payTotalSums = [];

        if ($cashierIds->isNotEmpty()) {
            $prefix      = DB::getTablePrefix();
            $pagePayData = DB::table('cashier_pay as cp')
                ->select('cp.accounts_id', DB::raw("SUM({$prefix}cp.income) as total"))
                ->whereIn('cp.cashier_id', $cashierIds)
                ->groupBy('cp.accounts_id')
                ->pluck('total', 'accounts_id');

            foreach ($accounts as $account) {
                $payPageSums["pay_{$account->id}"] = floatval($pagePayData->get($account->id, 0));
            }
        } else {
            // 没有数据时，初始化所有支付方式为 0
            foreach ($accounts as $account) {
                $payPageSums["pay_{$account->id}"] = 0;
            }
        }

        $prefix       = DB::getTablePrefix();
        $totalPayData = (clone $payQuery)
            ->select('cp.accounts_id', DB::raw("SUM({$prefix}cp.income) as total"))
            ->groupBy('cp.accounts_id')
            ->pluck('total', 'accounts_id');

        foreach ($accounts as $account) {
            $payTotalSums["pay_{$account->id}"] = floatval($totalPayData->get($account->id, 0));
        }

        $footer = [
            array_merge(
                [
                    'id'        => '页小计:',
                    'payable'   => floatval($items->sum('payable')),
                    'income'    => floatval($items->sum('income')),
                    'arrearage' => floatval($items->sum('arrearage')),
                ],
                $payPageSums
            ),
            array_merge(
                [
                    'id'        => '总合计:',
                    'payable'   => floatval($table->sum('payable')),
                    'income'    => floatval($table->sum('income')),
                    'arrearage' => floatval($table->sum('arrearage')),
                ],
                $payTotalSums
            )
        ];

        return response_success([
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ]);
    }

    /**
     * 应收账款表
     * @param ReportCashierRequest $request
     * @return JsonResponse
     */
    public function arrearage(ReportCashierRequest $request): JsonResponse
    {
        $date    = $request->input('date');
        $rows    = $request->input('rows', 10);
        $prefix  = DB::getTablePrefix();
        $keyword = $request->input('keyword');

        $startDate = Carbon::parse($date[0])->startOfDay();
        $endDate   = Carbon::parse($date[1])->endOfDay();

        // 计算期初欠款（查询日期开始前该顾客的累计欠款）
        $beginning = DB::table('cashier_arrearage as a')
            ->select(DB::raw("COALESCE(SUM({$prefix}a.leftover), 0)"))
            ->where('a.customer_id', '=', DB::raw("{$prefix}c.customer_id"))
            ->where('a.created_at', '<', $startDate);

        // 计算期末欠款（查询日期结束时该顾客的累计欠款）
        $ending = DB::table('cashier_arrearage as b')
            ->select(DB::raw("COALESCE(SUM({$prefix}b.leftover), 0)"))
            ->where('b.customer_id', '=', DB::raw("{$prefix}c.customer_id"))
            ->where('b.created_at', '<=', $endDate);

        // 构建查询 - 从欠款表开始，按顾客分组
        $builder = DB::table('cashier_arrearage as c')
            ->select([
                'c.customer_id',
                DB::raw("{$prefix}customer.name as customer_name"),
                DB::raw("{$prefix}customer.idcard"),
            ])
            ->selectSub($beginning, 'beginning_arrearage')
            ->selectSub($ending, 'ending_arrearage')
            ->addSelect(DB::raw("SUM(CASE WHEN {$prefix}c.created_at >= '{$startDate->toDateTimeString()}' AND {$prefix}c.created_at <= '{$endDate->toDateTimeString()}' THEN {$prefix}c.arrearage ELSE 0 END) AS new_arrearage"))
            ->addSelect(DB::raw("(SELECT COALESCE(SUM({$prefix}cad.income), 0) FROM {$prefix}cashier_arrearage_detail {$prefix}cad JOIN {$prefix}cashier_arrearage {$prefix}ca_ref ON {$prefix}ca_ref.id = {$prefix}cad.cashier_arrearage_id WHERE {$prefix}ca_ref.customer_id = {$prefix}c.customer_id AND {$prefix}cad.created_at >= '{$startDate->toDateTimeString()}' AND {$prefix}cad.created_at <= '{$endDate->toDateTimeString()}') AS repayment"))
            ->addSelect(DB::raw("SUM(CASE WHEN {$prefix}c.status = 3 AND {$prefix}c.created_at >= '{$startDate->toDateTimeString()}' AND {$prefix}c.created_at <= '{$endDate->toDateTimeString()}' THEN {$prefix}c.leftover ELSE 0 END) AS waiver"))
            ->addSelect(DB::raw("COUNT(DISTINCT CASE WHEN {$prefix}c.status = 1 AND {$prefix}c.leftover > 0 THEN {$prefix}c.id END) AS arrearage_count"))
            ->leftJoin('customer', 'customer.id', '=', 'c.customer_id')
            ->where('c.created_at', '<=', $endDate)
            ->when($keyword, fn(QueryBuilder $query) => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            ->groupBy('c.customer_id', 'customer.id', 'customer.name', 'customer.idcard')
            ->orderByDesc('ending_arrearage');

        // 查询
        $result = $builder->clone()
            ->having('ending_arrearage', '>', 0)
            ->orHaving('new_arrearage', '>', 0)
            ->orHaving('repayment', '>', 0)
            ->orHaving('waiver', '>', 0)
            ->paginate($rows);

        // 合计
        $items = collect($result->items());
        $table = DB::query()->fromSub($builder->clone(), 'sub');

        $footer = [
            [
                'idcard'              => '页小计:',
                'beginning_arrearage' => floatval($items->sum('beginning_arrearage')),
                'new_arrearage'       => floatval($items->sum('new_arrearage')),
                'repayment'           => floatval($items->sum('repayment')),
                'waiver'              => floatval($items->sum('waiver')),
                'ending_arrearage'    => floatval($items->sum('ending_arrearage')),
                'arrearage_count'     => floatval($items->sum('arrearage_count')),
            ],
            [
                'idcard'              => '总合计:',
                'beginning_arrearage' => floatval($table->sum('beginning_arrearage')),
                'new_arrearage'       => floatval($table->sum('new_arrearage')),
                'repayment'           => floatval($table->sum('repayment')),
                'waiver'              => floatval($table->sum('waiver')),
                'ending_arrearage'    => floatval($table->sum('ending_arrearage')),
                'arrearage_count'     => floatval($table->sum('arrearage_count')),
            ]
        ];

        return response_success([
            'rows'   => $result->items(),
            'total'  => $result->total(),
            'footer' => $footer
        ]);
    }

    /**
     * 应收账款明细表
     * @param ReportCashierRequest $request
     * @return JsonResponse
     */
    public function arrearageDetail(ReportCashierRequest $request): JsonResponse
    {
        $date    = $request->input('date');
        $rows    = $request->input('rows', 10);
        $page    = $request->input('page', 1);
        $keyword = $request->input('keyword');
        $type    = $request->input('type');

        $startDate = Carbon::parse($date[0])->startOfDay();
        $endDate   = Carbon::parse($date[1])->endOfDay();

        // 欠款单查询
        $arrearageQuery = DB::table('cashier_arrearage as ca')
            ->select([
                'ca.id',
                'ca.created_at',
                DB::raw("'arrearage' as type"),
                DB::raw("'欠款单' as type_name"),
                'ca.customer_id',
                'customer.name as customer_name',
                'customer.idcard',
                'ca.package_name',
                'ca.product_name',
                'ca.goods_name',
                'ca.times',
                'ca.specs',
                'ca.arrearage as income',
                DB::raw('NULL as remark'),
                'ca.salesman',
                'ca.department_id',
                'ca.user_id',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'ca.customer_id')
            ->whereBetween('ca.created_at', [$startDate, $endDate])
            ->when($keyword, fn($query) => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            ->when($type === 'arrearage' || $type === null || $type === '', fn($q) => $q)
            ->when($type === 'repayment', fn($q) => $q->whereRaw('1=0')); // 过滤掉欠款单

        // 还款单查询
        $repaymentQuery = DB::table('cashier_arrearage_detail as cad')
            ->select([
                'cad.id',
                'cad.created_at',
                DB::raw("'repayment' as type"),
                DB::raw("'还款单' as type_name"),
                'cad.customer_id',
                'customer.name as customer_name',
                'customer.idcard',
                'cad.package_name',
                'cad.product_name',
                'cad.goods_name',
                'cad.times',
                'cad.specs',
                'cad.income',
                'cad.remark',
                'cad.salesman',
                'cad.department_id',
                'cad.user_id',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'cad.customer_id')
            ->whereBetween('cad.created_at', [$startDate, $endDate])
            ->when($keyword, fn(QueryBuilder $query) => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            ->when($type === 'repayment' || $type === null || $type === '', fn($q) => $q)
            ->when($type === 'arrearage', fn($q) => $q->whereRaw('1=0')); // 过滤掉还款单

        // 合并查询
        $query = DB::query()->fromSub($arrearageQuery, 'arrearage')->unionAll($repaymentQuery);

        // 获取所有数据
        $allData = DB::table(DB::raw("({$query->toSql()}) as combined_data"))
            ->mergeBindings($query)
            ->orderBy('created_at', 'desc')
            ->get();

        // 手动分页
        $total  = $allData->count();
        $offset = ($page - 1) * $rows;
        $items  = $allData->slice($offset, $rows)->values();

        // 获取科室名称和用户名称
        $departmentIds = $items->pluck('department_id')->unique()->filter();
        $userIds       = $items->pluck('user_id')->unique()->filter();

        $departments = [];
        $users       = [];

        if ($departmentIds->isNotEmpty()) {
            $departments = Department::whereIn('id', $departmentIds)
                ->pluck('name', 'id')
                ->toArray();
        }

        if ($userIds->isNotEmpty()) {
            $users = DB::table('users')
                ->whereIn('id', $userIds)
                ->pluck('name', 'id')
                ->toArray();
        }

        // 填充科室和用户名称
        $items->transform(function ($item) use ($departments, $users) {
            $item->department_name = $departments[$item->department_id] ?? '';
            $item->user_name       = $users[$item->user_id] ?? '';
            return $item;
        });

        // 计算合计
        $footer = [
            [
                'type_name' => '页小计:',
                'income'    => floatval($items->sum('income')),
            ],
            [
                'type_name' => '总合计:',
                'income'    => floatval($allData->sum('income')),
            ]
        ];

        return response_success([
            'rows'   => $items,
            'total'  => $total,
            'footer' => $footer
        ]);
    }
}
