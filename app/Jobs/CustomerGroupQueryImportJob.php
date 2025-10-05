<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CustomerGroupQueryImportJob implements ShouldQueue
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
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $tenant_id, int $customer_group_id, string $sql)
    {
        $this->sql               = $sql;
        $this->tenant_id         = $tenant_id;
        $this->customer_group_id = $customer_group_id;
    }

    public function handle(): void
    {
        tenancy()->initialize($this->tenant_id);

        // 设置分群状态为导入中
        DB::table('customer_groups')->where('id', $this->customer_group_id)->update(['processing' => 1]);

        // 写入分群数据
        DB::table('customer_group_details')->insertUsing(
            [
                'customer_id',
                'customer_group_id',
                'created_at',
                'updated_at'
            ],
            DB::table('customer')
                ->select([
                    'customer.id',
                    DB::raw($this->customer_group_id),
                    DB::raw('NOW()'),
                    DB::raw('NOW()'),
                ])
                ->whereIn('id', function ($query) {
                    $query->select('customer_id')->from(DB::raw("({$this->sql}) as t"));
                })
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('customer_group_details')
                        ->whereColumn('customer_group_details.customer_id', 'customer.id')
                        ->where('customer_group_details.customer_group_id', $this->customer_group_id);
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
}
