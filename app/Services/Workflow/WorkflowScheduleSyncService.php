<?php

namespace App\Services\Workflow;

use App\Enums\WorkflowStatus;
use App\Enums\WorkflowType;
use App\Models\Workflow;
use InvalidArgumentException;

class WorkflowScheduleSyncService
{
    public function __construct(private readonly WorkflowPeriodicScheduler $scheduler) {}

    public function sync(Workflow $workflow, bool $forceCurrentPeriod = false): void
    {
        if ($workflow->type === WorkflowType::PERIODIC) {
            $periodicConfig = $this->scheduler->extractPeriodicConfig(
                is_array($workflow->rule_chain) ? $workflow->rule_chain : []
            );

            if ($periodicConfig === null) {
                throw new InvalidArgumentException('周期型工作流缺少开始节点配置');
            }

            $workflow->cron = $this->scheduler->serializeConfig($periodicConfig);
            $workflow->next_run_at = $workflow->status === WorkflowStatus::ACTIVE
                ? $this->scheduler->calculateNextRunAt($periodicConfig, null, $forceCurrentPeriod)
                : null;
            $workflow->save();

            return;
        }

        $workflow->cron = null;
        $workflow->next_run_at = null;
        $workflow->save();
    }
}
