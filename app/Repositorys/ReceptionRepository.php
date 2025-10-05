<?php

namespace App\Repositorys;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\Medium;
use App\Models\Reception;
use App\Models\ReceptionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;


class ReceptionRepository
{

    /**
     * 咨询成功率分析表
     * @param Request $request
     * @return array
     */
    public function product(Request $request): array
    {
        $item_id     = $request->input('item_id', 1);
        $item        = Item::query()->find($item_id);
        $items       = $item->child ? Item::query()->where('parentid', $item->id)->get() : [$item];
        $types       = ReceptionType::query()->get();
        $data        = [];
        $selectRaw   = [];
        $tablePrefix = DB::getTablePrefix();

        // 查询字段
        foreach ($types as $type) {
            $selectRaw[] = "count( DISTINCT (CASE WHEN {$tablePrefix}reception.type = {$type->id} THEN 1 ELSE NULL END )) AS total{$type->id}"; // 门诊量
            $selectRaw[] = "sum( DISTINCT (CASE WHEN {$tablePrefix}reception.STATUS = 2 and {$tablePrefix}reception.type = {$type->id} THEN 1 ELSE 0 END )) AS number{$type->id}"; // 成交量
            $selectRaw[] = "sum( CASE WHEN {$tablePrefix}reception.type = {$type->id} and {$tablePrefix}reception.STATUS = 2 THEN {$tablePrefix}cashier.income + {$tablePrefix}cashier.deposit END ) AS amount{$type->id}"; // 成交金额
        }

        // 查询结果
        $results = Reception::query()
            ->select('reception.consultant')
            ->addSelect('reception_items.reception_id')
            ->addSelect('reception_items.item_id')
            ->selectRaw(implode(',', $selectRaw))
            ->join('reception_items', 'reception.id', '=', 'reception_items.reception_id')
            ->leftJoin('cashier', function ($join) {
                $join->on('cashier.cashierable_id', '=', 'reception.id')
                    ->where('cashier.cashierable_type', '=', 'App\Models\Consultant')
                    ->where('cashier.status', 2);
            })
            // 分诊时间
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('reception.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            // 咨询师
            ->when($request->input('consultant'), function (Builder $query) use ($request) {
                $query->where('reception.consultant', $request->input('consultant'));
            })
            // 媒介来源
            ->when($request->input('medium_id') && $request->input('medium_id') !== 1, function (Builder $query) use ($request) {
                $query->whereIn('reception.medium_id', Medium::query()->find($request->input('medium_id'))->getAllChild()->pluck('id'));
            })
            // 项目
            ->when($request->input('item_id') && $request->input('item_id') !== 1, function (Builder $query) use ($request, $item) {
                $query->whereIn('reception_items.item_id', $item->getAllChild()->pluck('id'));
            })
            ->groupBy('reception.consultant')
            ->groupBy('reception_items.reception_id')
            ->groupBy('reception_items.item_id')
            ->get()
            ->toArray();

        // 无数据
        if (count($results) == 0) {
            return [];
        }

        // 获取项目分类所有子节点
        $items->map(function ($item) {
            $item->allchild = $item->getAllChild()->pluck('id');
        });

        $index  = 1;
        $users  = collect($results)->pluck('consultant')->unique()->toArray(); // 现场咨询
        $fields = collect($results[0])->except(['consultant', 'reception_id', 'item_id'])->keys()->toArray(); // 所有字段

        // 按项目大类合并小类
        foreach ($items as $item) {
            foreach ($users as $consultant) {
                $array = [
                    'id'         => $index,
                    'consultant' => $consultant,
                    'item_id'    => $item->id,
                    'item_name'  => $item->name
                ];
                foreach ($fields as $field) {
                    $array[$field] = collect($results)->where('consultant', $consultant)->whereIn('item_id', $item->allchild)->sum($field);
                }
                $data[] = $array;
                $index++;
            }
        }

        return $data;
    }

    /**
     * 现场咨询成功率分析表之明细
     * @param Request $request
     * @return array
     */
    public function receptionProductAnalysisDetail(Request $request): array
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $data  = [];
        $query = Reception::query()
            ->with(['customer:id,idcard,name', 'orders'])
            ->select('reception.id', 'reception_items.item_id', 'reception.type', 'reception.status', 'reception.customer_id', 'reception.created_at', 'reception.remark')
            ->leftJoin('reception_items', 'reception_items.reception_id', '=', 'reception.id')
            // 业务日期
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('reception.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            // 项目
            ->when($request->input('item_id'), function (Builder $query) use ($request) {
                $query->whereIn('reception_items.item_id', Item::query()->find($request->input('item_id'))->getAllChild()->pluck('id'));
            })
            // 媒介来源
            ->when($request->input('medium_id') && $request->input('medium_id') !== 1, function (Builder $query) use ($request) {
                $query->whereIn('reception.medium_id', Medium::query()->find($request->input('medium_id'))->getAllChild()->pluck('id'));
            })
            // 咨询师
            ->when($request->input('consultant'), function (Builder $query) use ($request) {
                $query->where('reception.consultant', $request->input('consultant'));
            })
            // 成交状态
            ->when($request->input('status'), function (Builder $query) use ($request) {
                $query->where('reception.status', $request->input('status'));
            })
            // 分诊状态
            ->where('reception.type', $request->input('type'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        if ($query) {
            $data['rows']  = $query->items();
            $data['total'] = $query->total();
        } else {
            $data['rows']  = [];
            $data['total'] = 0;
        }

        return $data;
    }
}
