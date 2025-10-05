<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Concerns\HasATenantsOption;
use Stancl\Tenancy\Concerns\TenantAwareCommand;

class UpdateTreeCommand extends Command
{
    use TenantAwareCommand, HasATenantsOption;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-tree-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '修复tree字段';

    /**
     * 业务表名
     * @var string
     */
    protected string $table = 'medium';

    public function __construct()
    {
        parent::__construct();
        $this->specifyParameters();
    }

    public function handle(): void
    {
        $this->updateTree();
        $this->info('All tree fields have been updated successfully!');
    }

    private function updateTree($parentId = 0, $parentTree = ''): void
    {
        $children = DB::table($this->table)
            ->where('parentid', $parentId)
            ->orderBy('id')
            ->get();

        foreach ($children as $child) {
            $tree = $parentId === 0 ? "0-{$child->id}" : "$parentTree-{$child->id}";
            DB::table($this->table)
                ->where('id', $child->id)
                ->update(['tree' => $tree]);
            $this->updateTree($child->id, $tree); // Recursive call
        }
    }
}
