<?php

namespace App\Traits;

use App\Events\Web\WorkflowTriggerEvent;

/**
 * Trait 用于在模型中触发工作流事件
 */
trait WorkflowTrait
{
    /**
     * 触发工作流事件
     *
     * @param  string  $eventName  事件名称，如 'customer.created'
     * @param  array  $payload  附加数据
     */
    protected function triggerWorkflowEvent(string $eventName, array $payload = []): void
    {
        $entityId = $this->getKey();
        $entityType = strtolower(class_basename($this));

        // 合并默认数据
        $defaultPayload = $this->getDefaultPayload();
        $payload = array_merge($defaultPayload, $payload);

        // 通过事件系统触发工作流
        WorkflowTriggerEvent::dispatch(
            $eventName,
            $entityType,
            $entityId,
            $payload
        );
    }

    /**
     * 获取默认的 payload 数据
     */
    protected function getDefaultPayload(): array
    {
        // 如果指定了字段白名单，直接过滤
        if (property_exists($this, 'workflowPayloadFields')) {
            return $this->only($this->workflowPayloadFields);
        }

        // 默认返回模型的所有可见属性
        return $this->toArray();
    }

    /**
     * 获取标准化的工作流事件名称
     *
     * @param  string  $action  动作名称，如 'created'
     */
    protected function getWorkflowEventName(string $action): string
    {
        return sprintf('%s.%s', strtolower(class_basename($this)), $action);
    }

    /**
     * 自动注册模型事件
     *
     * Laravel 会自动调用 boot{TraitName} 方法
     */
    protected static function bootWorkflowTrait(): void
    {
        $events = ['created', 'updated', 'deleted', 'restored', 'forceDeleted'];

        foreach ($events as $event) {
            if (method_exists(static::class, $event)) {
                static::$event(function ($model) use ($event) {
                    // 使用封装的方法生成事件名并触发
                    $model->triggerWorkflowEvent($model->getWorkflowEventName($event));
                });
            }
        }
    }

    /**
     * 注册模型事件来触发工作流
     *
     * 在模型的 booted 方法中调用此方法（覆盖默认行为）
     *
     * @param  array  $events  要监听的事件，如 ['created', 'updated']
     * @param  array  $eventNameMap  事件名称映射，如 ['created' => 'customer.created']
     */
    protected static function registerWorkflowEvents(array $events = ['created'], array $eventNameMap = []): void
    {
        foreach ($events as $event) {
            static::$event(function ($model) use ($event, $eventNameMap) {
                // 优先使用映射表中的名称，否则生成默认名称
                $eventName = $eventNameMap[$event] ?? $model->getWorkflowEventName($event);
                $model->triggerWorkflowEvent($eventName);
            });
        }
    }
}
