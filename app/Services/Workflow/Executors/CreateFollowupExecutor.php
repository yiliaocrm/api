<?php

namespace App\Services\Workflow\Executors;

use App\Enums\FollowupStatus;
use App\Models\Customer;
use App\Models\Followup;
use App\Models\WorkflowExecution;
use Carbon\Carbon;
use Throwable;

class CreateFollowupExecutor
{
    /**
     * 执行回访任务创建
     *
     * @param  WorkflowExecution  $execution  工作流执行实例
     * @param  array  $nodeConfig  节点配置
     * @return array 执行结果
     */
    public function execute(WorkflowExecution $execution, array $nodeConfig): array
    {
        $params = $nodeConfig['configuration'] ?? [];
        $context = is_array($execution->context_data) ? $execution->context_data : [];

        // 计算回访日期
        $followupDate = $this->calculateFollowupDate($params);

        // 从上下文获取客户ID（根据触发模型类型区分取值来源）
        $triggerModelType = strtolower((string) ($context['trigger']['model_type'] ?? ''));
        if ($triggerModelType === 'customer') {
            // 触发模型就是顾客，payload.id 即顾客 ID
            $customerId = $context['payload']['id']
                ?? $context['trigger']['model_id']
                ?? null;
        } else {
            // 非顾客模型触发，顾客 ID 在 payload.customer_id
            $customerId = $context['payload']['customer_id']
                ?? $context['customer_id']
                ?? null;
        }

        if (! $customerId) {
            return [
                'followup_id' => null,
                'created' => false,
                'error' => '无法获取客户ID',
            ];
        }

        // 校验客户是否存在
        if (! Customer::query()->where('id', $customerId)->exists()) {
            return [
                'followup_id' => null,
                'created' => false,
                'error' => "客户不存在: {$customerId}",
            ];
        }

        try {
            $triggerUserId = (int) ($execution->trigger_user_id ?? 0);
            $creatorUserId = $triggerUserId > 0 ? $triggerUserId : 1;

            // 解析提醒人员
            $followupUserId = $this->resolveFollowupUser($params, $customerId);

            if (! $followupUserId) {
                return [
                    'followup_id' => null,
                    'created' => false,
                    'error' => '无法确定提醒人员：归属关系字段为空且未配置兜底员工',
                ];
            }

            // 创建回访记录
            $followup = Followup::query()->create([
                'customer_id' => $customerId,
                'title' => (string) ($params['title'] ?? ''),
                'type' => (int) ($params['type'] ?? 0),
                'followup_user' => $followupUserId,
                'date' => $followupDate,
                'status' => FollowupStatus::PENDING->value,
                'user_id' => $creatorUserId,
            ]);

            return [
                'followup_id' => $followup->id,
                'created' => true,
                'followup_date' => $followupDate,
                'customer_id' => $customerId,
            ];
        } catch (Throwable $e) {
            return [
                'followup_id' => null,
                'created' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 解析提醒人员
     *
     * @param  array  $params  节点参数
     * @param  mixed  $customerId  客户ID
     * @return int|null 提醒人员用户ID
     */
    private function resolveFollowupUser(array $params, mixed $customerId): ?int
    {
        $mode = $params['followup_user_mode'] ?? 'specified';

        if ($mode === 'specified') {
            return (int) ($params['followup_user'] ?? 0) ?: null;
        }

        // relation 模式：从顾客记录读取归属字段
        $relationField = $params['followup_user_relation'] ?? null;
        $allowedFields = ['ascription', 'consultant', 'service_id', 'doctor_id'];

        if (! $relationField || ! in_array($relationField, $allowedFields)) {
            return null;
        }

        $customer = Customer::query()->find($customerId);

        if ($customer) {
            $userId = (int) ($customer->{$relationField} ?? 0);
            if ($userId > 0) {
                return $userId;
            }
        }

        // 兜底员工
        if (! empty($params['followup_user_fallback'])) {
            $fallbackUserId = (int) ($params['followup_user_fallback_user'] ?? 0);
            if ($fallbackUserId > 0) {
                return $fallbackUserId;
            }
        }

        return null;
    }

    /**
     * 计算回访日期
     *
     * @param  array  $params  节点参数
     * @return string 回访日期 (Y-m-d)
     */
    private function calculateFollowupDate(array $params): string
    {
        $dateMode = $params['date_mode'] ?? 'relative';

        if ($dateMode === 'absolute') {
            // 绝对日期模式
            return Carbon::parse($params['absolute_date'])->toDateString();
        }

        // 相对日期模式
        $offset = $params['date_offset'] ?? 0;
        $unit = $params['date_unit'] ?? 'days';

        return match ($unit) {
            'hours' => now()->addHours($offset)->toDateString(),
            'days' => now()->addDays($offset)->toDateString(),
            'weeks' => now()->addWeeks($offset)->toDateString(),
            default => now()->addDays($offset)->toDateString(),
        };
    }
}
