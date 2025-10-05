<?php

namespace App\Console\Commands\Tenant;

use App\Models\Tenant;
use App\Models\CustomerGroup;
use App\Helpers\ParseCdpField;
use Illuminate\Console\Command;
use App\Jobs\CustomerGroupComputingJob;

class CustomerGroupComputingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:customer-group-computing-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '计算客户分群';

    public function handle(): void
    {
        Tenant::query()->where('status', 'run')->get()->runForEach(function ($tenant) {
            CustomerGroup::query()
                ->where('type', '<>', 'static')
                ->where('processing', 0)
                ->get()
                ->each(function ($group) use ($tenant) {
                    // 如果分群类型为动态，重新生成 SQL
                    if ($group->type === 'dynamic') {
                        $parser     = new ParseCdpField();
                        $group->sql = $parser->filter($group->filter_rule)->exclude($group->exclude_rule)->getSql();
                        $group->save();
                    }
                    dispatch(new CustomerGroupComputingJob($tenant->id, $group->id, $group->sql));
                });
        });
    }
}
