<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ReportConsultantRequest;
use App\Models\Item;
use App\Models\Medium;
use App\Models\Reception;
use App\Models\ReceptionOrder;
use App\Models\ReceptionType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportConsultantController extends Controller
{
    /**
     * 咨询成功率分析表
     */
    public function receptionProductAnalysis(Request $request): JsonResponse
    {
        $itemId = $request->input('item_id', 1);
        $item = Item::query()->find($itemId);

        $types = ReceptionType::query()
            ->orderBy('id')
            ->get(['id', 'name', 'remark']);

        // Preserve the legacy row semantics:
        // - If selected item is a parent, report rows for its direct children.
        // - Otherwise, report the selected leaf item itself.
        $reportItems = $item->child
            ? Item::query()->where('parentid', $item->id)->orderBy('id')->get()
            : collect([$item]);

        $descendantToReportItem = [];
        foreach ($reportItems as $reportItem) {
            // getAllChild() is used in the legacy implementation to bucket descendants under each reported item.
            $descendantIds = $reportItem->getAllChild()->pluck('id')->all();
            $reportItem->allchild = $descendantIds;

            foreach ($descendantIds as $descendantId) {
                $descendantToReportItem[$descendantId] = $reportItem->id;
            }
        }

        // Reduce overcount risk by pre-aggregating cashier totals per reception.
        $cashierTotals = DB::table('cashier')
            ->select('cashierable_id')
            ->selectRaw('sum(income + deposit) as amount')
            ->where('cashierable_type', '=', 'App\\Models\\Consultant')
            ->where('status', 2)
            ->groupBy('cashierable_id');
        $cashierTotalsAmountColumn = DB::connection()->getQueryGrammar()->wrap('cashier_totals.amount');

        $results = Reception::query()
            ->select([
                'reception.id as reception_id',
                'reception.consultant',
                'users.name as consultant_name',
                'reception_items.item_id',
                'reception.type as type_id',
                'reception.status as reception_status',
            ])
            ->selectRaw("coalesce({$cashierTotalsAmountColumn}, 0) as reception_amount")
            ->join('reception_items', 'reception.id', '=', 'reception_items.reception_id')
            ->leftJoin('users', 'users.id', '=', 'reception.consultant')
            ->leftJoinSub($cashierTotals, 'cashier_totals', function ($join) {
                $join->on('cashier_totals.cashierable_id', '=', 'reception.id');
            })
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('reception.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay(),
                ]);
            })
            ->when($request->input('consultant'), function (Builder $query) use ($request) {
                $query->where('reception.consultant', $request->input('consultant'));
            })
            ->when($request->input('medium_id') && $request->input('medium_id') !== 1, function (Builder $query) use ($request) {
                $query->whereIn('reception.medium_id', Medium::query()->find($request->input('medium_id'))->getAllChild()->pluck('id'));
            })
            ->when($request->input('item_id') && $request->input('item_id') !== 1, function (Builder $query) use ($item) {
                $query->whereIn('reception_items.item_id', $item->getAllChild()->pluck('id'));
            })
            ->get();

        if ($results->isEmpty()) {
            return response_success([
                'types' => $types,
                'rows' => [],
            ]);
        }

        $typeIds = $types->pluck('id')->map(fn ($id) => (string) $id)->all();
        $consultants = $results->pluck('consultant')->unique()->values();

        // Capture consultant display names from observed results.
        $consultantNames = [];
        foreach ($results as $result) {
            $key = $this->consultantKey($result->consultant ?? null);
            if (! array_key_exists($key, $consultantNames)) {
                $consultantNames[$key] = (string) ($result->consultant_name ?? '');
            }
        }

        // Fold results into a bucket keyed by consultant + reportItem + type, deduping per reception.
        $bucket = [];
        $seen = [];
        foreach ($results as $result) {
            $rawItemId = (int) ($result->item_id ?? 0);
            $reportItemId = $descendantToReportItem[$rawItemId] ?? null;
            if ($reportItemId === null) {
                continue;
            }

            $consultantKey = $this->consultantKey($result->consultant ?? null);
            $typeKey = (string) ($result->type_id ?? '');
            if ($typeKey === '') {
                continue;
            }

            $receptionId = (int) ($result->reception_id ?? 0);
            if ($receptionId <= 0) {
                continue;
            }

            if (isset($seen[$consultantKey][$reportItemId][$typeKey][$receptionId])) {
                continue;
            }

            $seen[$consultantKey][$reportItemId][$typeKey][$receptionId] = true;

            $bucket[$consultantKey][$reportItemId][$typeKey]['total'] = ($bucket[$consultantKey][$reportItemId][$typeKey]['total'] ?? 0) + 1;

            $status = (int) ($result->reception_status ?? 0);
            if ($status === 2) {
                $bucket[$consultantKey][$reportItemId][$typeKey]['number'] = ($bucket[$consultantKey][$reportItemId][$typeKey]['number'] ?? 0) + 1;
                $bucket[$consultantKey][$reportItemId][$typeKey]['amount'] = ($bucket[$consultantKey][$reportItemId][$typeKey]['amount'] ?? 0) + (float) ($result->reception_amount ?? 0);
            }
        }

        // Build full row matrix: direct report items x consultants found in results.
        $rows = [];
        $index = 1;
        foreach ($reportItems as $reportItem) {
            foreach ($consultants as $consultant) {
                $consultantKey = $this->consultantKey($consultant);

                $receptionTypes = [];
                foreach ($typeIds as $typeId) {
                    $metrics = $bucket[$consultantKey][$reportItem->id][$typeId] ?? null;
                    $receptionTypes[$typeId] = $metrics === null
                        ? $this->defaultReceptionMetrics()
                        : $this->formatReceptionMetrics(
                            $metrics['total'] ?? 0,
                            $metrics['number'] ?? 0,
                            $metrics['amount'] ?? 0
                        );
                }

                $rows[] = [
                    'id' => $index,
                    'consultant' => $consultant,
                    'consultant_name' => $consultantNames[$consultantKey] ?? '',
                    'item_id' => $reportItem->id,
                    'item_name' => $reportItem->name,
                    'reception_types' => $receptionTypes,
                ];

                $index++;
            }
        }

        return response_success([
            'types' => $types,
            'rows' => $rows,
        ]);
    }

    private function consultantKey(mixed $consultant): string
    {
        return $consultant === null ? '__null__' : (string) $consultant;
    }

    private function defaultReceptionMetrics(): array
    {
        return [
            'total' => 0,
            'number' => 0,
            'rate' => '0.00%',
            'amount' => 0,
            'average' => '0.00',
        ];
    }

    private function formatReceptionMetrics(float|int $total, float|int $number, float|int $amount): array
    {
        $rate = $total > 0 ? number_format(($number / $total) * 100, 2).'%' : '0.00%';
        $average = $number > 0 ? number_format($amount / $number, 2, '.', '') : '0.00';

        return [
            'total' => (int) $total,
            'number' => (int) $number,
            'rate' => $rate,
            'amount' => (float) $amount,
            'average' => $average,
        ];
    }

    /**
     * 现场咨询成功率分析表之明细
     */
    public function receptionProductAnalysisDetail(Request $request): JsonResponse
    {
        $rows = $request->input('rows', 10);
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');

        $query = Reception::query()
            ->with(['customer:id,idcard,name', 'orders'])
            ->select('reception.id', 'reception_items.item_id', 'reception.type', 'reception.status', 'reception.customer_id', 'reception.created_at', 'reception.remark')
            ->leftJoin('reception_items', 'reception_items.reception_id', '=', 'reception.id')
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('reception.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay(),
                ]);
            })
            ->when($request->input('item_id'), function (Builder $query) use ($request) {
                $query->whereIn('reception_items.item_id', Item::query()->find($request->input('item_id'))->getAllChild()->pluck('id'));
            })
            ->when($request->input('medium_id') && $request->input('medium_id') !== 1, function (Builder $query) use ($request) {
                $query->whereIn('reception.medium_id', Medium::query()->find($request->input('medium_id'))->getAllChild()->pluck('id'));
            })
            ->when($request->input('consultant'), function (Builder $query) use ($request) {
                $query->where('reception.consultant', $request->input('consultant'));
            })
            ->when($request->input('status'), function (Builder $query) use ($request) {
                $query->where('reception.status', $request->input('status'));
            })
            ->where('reception.type', $request->input('type'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows' => $query ? $query->items() : [],
            'total' => $query ? $query->total() : 0,
        ]);
    }

    /**
     * 现场开单明细表
     */
    public function order(ReportConsultantRequest $request): JsonResponse
    {
        $rows = $request->input('rows', 10);
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $keyword = $request->input('keyword');

        $builder = ReceptionOrder::query()
            ->with([
                'user:id,name',
                'customer:id,name,idcard',
                'department:id,name',
                'reception',
                'reception.medium:id,name',
                'reception.department:id,name',
                'reception.consultantUser:id,name',
                'reception.receptionType:id,name',
                'reception.receptionItems',
            ])
            ->select([
                'reception_order.*',
            ])
            ->leftJoin('reception', 'reception.id', '=', 'reception_order.reception_id')
            ->leftJoin('customer', 'customer.id', '=', 'reception_order.customer_id')
            ->queryConditions('ReportConsultantOrder')
            ->whereBetween('reception_order.created_at', [
                Carbon::parse($request->input('created_at.0'))->startOfDay(),
                Carbon::parse($request->input('created_at.1'))->endOfDay(),
            ])
            ->when($keyword, fn (Builder $query) => $query->where('customer.keyword', 'like', '%'.$keyword.'%'))
            ->orderBy("reception_order.{$sort}", $order);

        $query = $builder->clone()->paginate($rows);
        $items = collect($query->items());
        $table = $builder->clone();

        $footer = [
            [
                'product_name' => '页小计:',
                'payable' => $items->sum('payable'),
                'amount' => $items->sum('amount'),
            ],
            [
                'product_name' => '总合计:',
                'payable' => floatval($table->sum('reception_order.payable')),
                'amount' => floatval($table->sum('reception_order.amount')),
            ],
        ];

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
            'footer' => $footer,
        ]);
    }

    /**
     * 现场咨询明细表
     */
    public function detail(ReportConsultantRequest $request): JsonResponse
    {
        $rows = $request->input('rows', 10);
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $keyword = $request->input('keyword');

        $query = Reception::query()
            ->with([
                'user:id,name',
                'customer:id,name,idcard',
                'department:id,name',
                'medium:id,name',
                'consultantUser:id,name',
                'receptionType:id,name',
                'receptionItems',
                'failure:id,name',
                'ekUserRelation:id,name',
                'doctorUser:id,name',
                'receptionUser:id,name',
                'orders' => function ($query) {
                    $query->with([
                        'department:id,name',
                        'user:id,name',
                    ])->orderBy('created_at', 'desc');
                },
            ])
            ->select([
                'reception.*',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'reception.customer_id')
            ->queryConditions('ReportConsultantDetailIndex')
            ->whereBetween('reception.created_at', [
                Carbon::parse($request->input('created_at.0'))->startOfDay(),
                Carbon::parse($request->input('created_at.1'))->endOfDay(),
            ])
            ->when($keyword, fn (Builder $query) => $query->where('customer.keyword', 'like', '%'.$keyword.'%'))
            ->orderBy("reception.{$sort}", $order)
            ->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }
}
