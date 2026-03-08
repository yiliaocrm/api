<?php

namespace App\Services\Workflow;

use App\Enums\WorkflowExecutionStatus;
use App\Models\WorkflowExecution;
use App\Models\WorkflowExecutionStep;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class WorkflowExecutionCleanupService
{
    /**
     * @return array{
     *   matched_executions: int,
     *   deleted_steps: int,
     *   deleted_executions: int,
     *   batches: int
     * }
     */
    public function cleanupExpiredExecutions(CarbonInterface $cutoff, int $chunk = 500, bool $dryRun = false): array
    {
        $chunk = max(1, $chunk);
        $stats = [
            'matched_executions' => 0,
            'deleted_steps' => 0,
            'deleted_executions' => 0,
            'batches' => 0,
        ];

        $query = $this->buildExpiredExecutionsQuery($cutoff);
        $stats['matched_executions'] = (clone $query)->count();

        if ($dryRun || $stats['matched_executions'] === 0) {
            return $stats;
        }

        while (true) {
            $executionIds = (clone $query)
                ->orderBy('id')
                ->limit($chunk)
                ->pluck('id')
                ->all();

            if (empty($executionIds)) {
                break;
            }

            $stats['batches']++;
            $stats['deleted_steps'] += WorkflowExecutionStep::query()
                ->whereIn('workflow_execution_id', $executionIds)
                ->delete();

            $stats['deleted_executions'] += WorkflowExecution::query()
                ->whereIn('id', $executionIds)
                ->delete();
        }

        return $stats;
    }

    private function buildExpiredExecutionsQuery(CarbonInterface $cutoff): Builder
    {
        return WorkflowExecution::query()
            ->whereIn('status', $this->terminalStatuses())
            ->where(function (Builder $query) use ($cutoff) {
                $query->where('finished_at', '<', $cutoff)
                    ->orWhere(function (Builder $fallbackQuery) use ($cutoff) {
                        $fallbackQuery->whereNull('finished_at')
                            ->where('created_at', '<', $cutoff);
                    });
            });
    }

    /**
     * @return array<int, string>
     */
    private function terminalStatuses(): array
    {
        return [
            WorkflowExecutionStatus::SUCCESS->value,
            WorkflowExecutionStatus::ERROR->value,
            WorkflowExecutionStatus::CANCELED->value,
        ];
    }
}

