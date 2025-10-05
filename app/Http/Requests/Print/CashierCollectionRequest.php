<?php

namespace App\Http\Requests\Print;

use Carbon\Carbon;
use App\Models\Accounts;
use App\Models\CashierPay;
use App\Models\CashierDetail;
use App\Models\PrintTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class CashierCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'created_at_start' => 'required|date',
            'created_at_end'   => [
                'required',
                'date',
                'after_or_equal:created_at_start',
                function ($attribute, $value, $fail) {
                    $print_template = PrintTemplate::query()
                        ->where('type', 'cashier_collection')
                        ->where('default', 1)
                        ->first();
                    if (!$print_template) {
                        $fail('[收费汇总表]打印模板不存在!');
                    }
                }
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'created_at_start.required'     => '[开始时间]必须填写',
            'created_at_start.date'         => '[开始时间]必须是日期格式',
            'created_at_end.required'       => '[结束时间]必须填写',
            'created_at_end.date'           => '[结束时间]必须是日期格式',
            'created_at_end.after_or_equal' => '[结束时间]必须大于等于[开始时间]',
        ];
    }

    public function getPrintTemplate()
    {
        return PrintTemplate::query()
            ->where('type', 'cashier_collection')
            ->where('default', 1)
            ->first();
    }

    public function getCashierCollection()
    {
        $accounts = Accounts::all();

        // Subquery 1
        $subQuery1 = DB::table('cashier_detail')
            ->select([
                DB::raw('date(created_at) as date'),
                DB::raw('count(DISTINCT cashier_id) as number'),
                DB::raw('SUM(income) as income'),
                DB::raw('SUM(deposit) as deposit'),
                DB::raw('SUM(CASE WHEN product_id <> 1 THEN income + deposit ELSE 0 END) as turnover'),
                DB::raw('SUM(arrearage) as arrearage'),
                DB::raw('SUM(CASE WHEN cashierable_type = "App\\Models\\CashierArrearage" THEN income ELSE 0 END) as repayment'),
                DB::raw('SUM(CASE WHEN cashierable_type = "App\\Models\\CashierRefund" THEN ABS(income) ELSE 0 END) as refund')
            ])
            ->whereBetween('created_at', [
                Carbon::parse($this->input('created_at_start')),
                Carbon::parse($this->input('created_at_end'))->endOfDay(),
            ])
            ->groupBy(DB::raw('date(created_at)'));

        $sql1      = $subQuery1->toSql();
        $bindings1 = $subQuery1->getBindings();

        // Subquery 2
        $subQuery2 = DB::table('cashier_pay')
            ->select([DB::raw('DATE(created_at) as date')])
            ->whereBetween('created_at', [
                Carbon::parse($this->input('created_at_start')),
                Carbon::parse($this->input('created_at_end'))->endOfDay(),
            ])
            ->groupBy(DB::raw('DATE(created_at)'));

        // 循环所有收费账户
        foreach ($accounts as $account) {
            $subQuery2->addSelect(DB::raw("SUM(CASE WHEN accounts_id = {$account->id} THEN income ELSE 0 END) as pay{$account->id}"));
        }

        $sql2      = $subQuery2->toSql();
        $bindings2 = $subQuery2->getBindings();

        // 连表查询
        $results = DB::table(DB::raw("($sql1) as cy_d"))
            ->addBinding($bindings1)
            ->leftJoin(DB::raw("($sql2) as cy_p"), 'd.date', '=', 'p.date')
            ->addBinding($bindings2)
            ->orderBy('d.date', 'desc')
            ->paginate($this->input('rows', 10));

        return [
            'rows'  => $results->items(),
            'total' => $results->total()
        ];
    }


}
