<?php

namespace App\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class CustomerGroupComputingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 租户ID
     * @var string
     */
    protected string $tenant_id;

    /**
     * 分群id
     * @var int
     */
    protected int $customer_group_id;

    /**
     * 执行分群sql
     * @var string
     */
    protected string $sql;

    /**
     * 顾客分群计算任务构造函数
     * @param string $tenant_id 租户ID
     * @param int $customer_group_id 分群ID
     * @param string $sql SQL语句
     */
    public function __construct(string $tenant_id, int $customer_group_id, string $sql)
    {
        $this->sql               = $sql;
        $this->tenant_id         = $tenant_id;
        $this->customer_group_id = $customer_group_id;
    }

    /**
     * @return void
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function handle(): void
    {
        tenancy()->initialize($this->tenant_id);

        // 设置分群状态为计算中
        DB::table('customer_groups')->where('id', $this->customer_group_id)->update([
            'count'      => 0,
            'processing' => 1
        ]);

        // 删除原有分群数据
        DB::table('customer_group_details')->where('customer_group_id', $this->customer_group_id)->delete();

        // 写入分群数据
        DB::table('customer_group_details')->insertUsing(
            [
                'customer_id',
                'customer_group_id',
                'created_at',
                'updated_at',
            ],
            DB::table('customer')
                ->select([
                    'id',
                    DB::raw($this->customer_group_id),
                    DB::raw('NOW()'),
                    DB::raw('NOW()')
                ])
                ->whereIn('id', function ($query) {
                    $query->select('customer_id')->from(DB::raw("({$this->sql}) as t"));
                })
        );

        // 更新分群最后计算时间
        DB::table('customer_groups')->where('id', $this->customer_group_id)->update([
            'count'             => DB::table('customer_group_details')->where('customer_group_id', $this->customer_group_id)->count(),
            'processing'        => 0,
            'last_execute_time' => now(),
        ]);

        tenancy()->end();
    }

    /**
     * 处理任务失败
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        DB::table('customer_groups')->where('id', $this->customer_group_id)->update([
            'processing' => 0
        ]);
    }
}
