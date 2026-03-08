<?php

namespace App\Traits;

use App\Events\Web\WorkflowTriggerEvent;

/**
 * 工作流触发器 Trait
 *
 * 用于模型自动触发工作流事件。
 * 使用此 Trait 的模型在创建、更新等操作时会自动触发工作流。
 */
trait WorkflowTrait
{
    /**
     * 触发工作流事件
     *
     * 将模型数据作为 payload 发送到工作流系统。
     * 会自动获取模型 ID、类型，并合并默认 payload 和自定义 payload。
     *
     * @param  string  $eventName  事件名称，例如：customer.created, customer.updated
     * @param  array<string, mixed>  $payload  额外的 payload 数据（可选），会与默认 payload 合并
     */
    protected function triggerWorkflowEvent(string $eventName, array $payload = []): void
    {
        // 获取模型主键 ID
        $modelId = $this->getKey();
        // 获取模型类型（小写类名），例如：customer
        $modelType = strtolower(class_basename($this));

        // 获取默认 payload（模型数据）
        $defaultPayload = $this->getDefaultPayload();
        // 合并默认 payload 和自定义 payload
        $payload = array_merge($defaultPayload, $payload);

        // 分发工作流触发事件
        WorkflowTriggerEvent::dispatch(
            $eventName,
            $modelType,
            $modelId,
            $payload
        );
    }

    /**
     * 获取默认 payload 数据
     *
     * 直接查询数据库获取模型数据，避免包含已加载的关联关系。
     * 这确保了 payload 数据轻量、可预测，不会因为预加载关联而导致数据过大。
     *
     * 两种模式：
     * 1. 如果模型定义了 $workflowPayloadFields 属性，只查询指定字段
     * 2. 如果未定义，查询所有列（但仍不包含关联关系）
     *
     * @return array<string, mixed> 模型数据数组
     */
    public function getDefaultPayload(): array
    {
        // 如果定义了 workflowPayloadFields，只查询指定字段
        if (property_exists($this, 'workflowPayloadFields')) {
            // 支持数组或字符串格式
            $fields = is_array($this->workflowPayloadFields)
                ? $this->workflowPayloadFields
                : [$this->workflowPayloadFields];

            // 创建新查询，只查询指定字段
            return $this->newQuery()
                ->where($this->getKeyName(), $this->getKey())
                ->first($fields)
                ?->toArray() ?? [];
        }

        // 否则查询所有列（不包含关联关系）
        return $this->newQuery()
            ->where($this->getKeyName(), $this->getKey())
            ->first()
            ?->toArray() ?? [];
    }

    /**
     * 构建默认的工作流事件名称
     *
     * 格式：模型名.操作名（小写）
     * 例如：customer.created, customer.updated
     *
     * @param  string  $action  操作名称，如：created, updated, deleted
     * @return string 完整的事件名称
     */
    protected function getWorkflowEventName(string $action): string
    {
        return sprintf('%s.%s', strtolower(class_basename($this)), $action);
    }

    /**
     * 自动注册模型事件以触发工作流
     *
     * 当模型使用此 Trait 时，会自动注册 created 和 updated 事件。
     * 这是 Laravel 的 Trait Boot 机制，会在模型启动时自动调用。
     *
     * 默认注册的事件：
     * - created: 模型创建后触发
     * - updated: 模型更新后触发
     */
    protected static function bootWorkflowTrait(): void
    {
        // 注册 created 事件：模型创建后触发工作流
        static::created(function ($model) {
            $model->triggerWorkflowEvent($model->getWorkflowEventName('created'));
        });

        // 注册 updated 事件：模型更新后触发工作流
        static::updated(function ($model) {
            $model->triggerWorkflowEvent($model->getWorkflowEventName('updated'));
        });
    }

    /**
     * 注册自定义工作流事件
     *
     * 在模型的 booted() 方法中调用此方法，可以自定义要监听的事件。
     * 如果不想使用默认的 created/updated 事件，可以通过此方法自定义。
     *
     * 使用示例：
     * ```php
     * protected static function booted()
     * {
     *     // 只监听 created 和 deleted 事件
     *     static::registerWorkflowEvents(['created', 'deleted']);
     *
     *     // 自定义事件名称映射
     *     static::registerWorkflowEvents(
     *         ['created', 'updated'],
     *         ['created' => 'customer.new', 'updated' => 'customer.modified']
     *     );
     * }
     * ```
     *
     * @param  array<int, string>  $events  要监听的事件列表，如：['created', 'updated', 'deleted']
     * @param  array<string, string>  $eventNameMap  事件名称映射，如：['created' => 'customer.new']
     */
    protected static function registerWorkflowEvents(array $events = ['created'], array $eventNameMap = []): void
    {
        // 遍历每个事件并注册监听器
        foreach ($events as $event) {
            static::$event(function ($model) use ($event, $eventNameMap) {
                // 如果有自定义事件名称映射，使用映射的名称，否则使用默认名称
                $eventName = $eventNameMap[$event] ?? $model->getWorkflowEventName($event);
                $model->triggerWorkflowEvent($eventName);
            });
        }
    }
}
