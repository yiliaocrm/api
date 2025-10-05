<?php

namespace App\Jobs;

use App\Models\Sms;
use App\Enums\SmsStatus;
use App\Models\Customer;
use App\Models\CustomerPhone;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Database\Eloquent\Builder;

class BatchSendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $params;

    /**
     * Create a new job instance.
     *
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $p = $this->params;

        // 启用显示原始手机号
        CustomerPhone::$showOriginalPhone = true;

        Customer::query()
            ->select(['customer.id'])
            ->when($p['keyword'], fn(Builder $query) => $query->where('customer.keyword', 'like', "%{$p['keyword']}%"))
            ->when($p['group_id'], fn(Builder $query) => $query->leftJoin('customer_group_details', 'customer_group_details.customer_id', '=', 'customer.id')
                ->where('customer_group_details.customer_group_id', $p['group_id'])
            )
            ->queryConditions('CustomerIndex')
            ->when($p['has_permission'], function (Builder $query) use ($p) {
                $query->where(function ($q) use ($p) {
                    $q->whereIn('customer.ascription', $p['permission_ids'])->orWhereIn('customer.consultant', $p['permission_ids']);
                });
            })
            ->orderBy('customer.' . $p['sort'], $p['order'])
            ->chunk(200, function ($customers) use ($p) {
                $customerIds = $customers->pluck('id')->toArray();

                // 使用子查询和窗口函数，为每个客户找出优先级最高的电话号码（relation_id 最小）
                $subQuery = CustomerPhone::query()
                    ->select(['customer_id', 'phone'])
                    ->selectRaw('ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY relation_id) as rn')
                    ->whereIn('customer_id', $customerIds)
                    ->whereNotNull('phone')
                    ->where('phone', '!=', '');

                // 筛选出每个客户优先级最高的电话
                $customerPhones = CustomerPhone::query()
                    ->fromSub($subQuery, 'cp')
                    ->where('rn', 1)
                    ->get();

                foreach ($customerPhones as $customerPhone) {
                    $sms = Sms::query()->create([
                        'template_id' => $p['template_id'],
                        'phone'       => $customerPhone->phone,
                        'content'     => '',
                        'channel'     => $p['channel'],
                        'status'      => SmsStatus::PENDING->value,
                        'user_id'     => $p['user_id'],
                        'scenario'    => 'customer',
                        'scenario_id' => $customerPhone->customer_id,
                    ]);
                    SendSmsJob::dispatch($sms);
                }
            });

        // 恢复隐藏手机号
        CustomerPhone::$showOriginalPhone = false;
    }
}
