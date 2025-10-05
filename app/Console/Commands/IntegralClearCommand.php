<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Concerns\HasATenantsOption;
use Stancl\Tenancy\Concerns\TenantAwareCommand;

class IntegralClearCommand extends Command
{
    use TenantAwareCommand, HasATenantsOption;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integral:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '积分清零';

    public function __construct()
    {
        parent::__construct();
        $this->specifyParameters();
    }


    public function handle(): void
    {
        $this->info('顾客积分清零中...');

        $where     = [
            ['created_at', '>=', '2009-01-01 00:00:00'],
            ['created_at', '<=', '2024-12-31 23:59:59'],
//            ['id', '=', '9512d1fb-37a2-4411-82d8-74585d4dc1ad']
        ];
        $total     = DB::table('customer')->where($where)->count();
        $chunkSize = min($total, 200);

        $bar = $this->output->createProgressBar(ceil($total / $chunkSize));
        $bar->start();

        DB::table('customer')->where($where)->orderByDesc('created_at')->chunk($chunkSize, function ($customers) use ($bar) {
            foreach ($customers as $customer) {

                // 有效积分合计
                $integral = max(DB::table('integral')
                    ->where('customer_id', $customer->id)
                    ->where('expired', 0)
                    ->sum('integral'), 0);

                // 最新一条积分记录
                $last = DB::table('integral')
                    ->where('customer_id', $customer->id)
                    ->orderByDesc('id')
                    ->first();

                DB::table('customer')->where('id', $customer->id)->update([
                    'integral' => $integral
                ]);

                if ($last) {
                    DB::table('integral')->insert([
                        'customer_id' => $customer->id,
                        'before'      => $last->after,
                        'integral'    => bcsub($integral, $last->after, 4),
                        'after'       => $integral,
                        'type'        => 7,
                        'remark'      => '2024-01-01之前积分清零',
                        'created_at'  => now(),
                        'updated_at'  => now()
                    ]);
                } else {
                    DB::table('integral')->insert([
                        'customer_id' => $customer->id,
                        'before'      => 0,
                        'integral'    => 0,
                        'after'       => 0,
                        'type'        => 7,
                        'remark'      => '2024-01-01之前积分清零',
                        'created_at'  => now(),
                        'updated_at'  => now()
                    ]);
                }

            }
            $bar->advance();
        });

        $this->info('顾客积分清零操作完成!');
    }
}
