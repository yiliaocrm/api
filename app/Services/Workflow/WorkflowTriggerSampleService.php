<?php

namespace App\Services\Workflow;

use App\Models\Customer;
use App\Models\Reservation;
use App\Models\WorkflowComponent;

class WorkflowTriggerSampleService
{
    private const array EVENT_MODEL_MAP = [
        'customer.created' => Customer::class,
        'customer.updated' => Customer::class,
        'reservation.created' => Reservation::class,
        'reservation.updated' => Reservation::class,
    ];

    /**
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $componentPreviewSamples = null;

    /**
     * Fetch real sample data for trigger event
     * Maps event to model and queries latest record
     *
     * @return array<string, mixed>
     */
    public function fetchTriggerSample(string $event): array
    {
        $modelClass = self::EVENT_MODEL_MAP[$event] ?? null;

        if (! $modelClass || ! class_exists($modelClass)) {
            return [
                'event' => $event,
                'sample_data' => $this->resolveMockData($event),
                'source' => 'mock',
                'fetched_at' => now()->toIso8601String(),
            ];
        }

        // Fetch latest record
        $record = $modelClass::query()->latest('id')->first();

        if (! $record) {
            return [
                'event' => $event,
                'sample_data' => $this->resolveMockData($event),
                'source' => 'mock',
                'fetched_at' => now()->toIso8601String(),
            ];
        }

        return [
            'event' => $event,
            'sample_data' => $record->toArray(),
            'source' => 'database',
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveMockData(string $event): array
    {
        $configured = $this->resolveConfiguredMockData($event);
        if (is_array($configured) && ! empty($configured)) {
            return $configured;
        }

        return $this->generateMockData($event);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveConfiguredMockData(string $event): ?array
    {
        if ($this->componentPreviewSamples === null) {
            $component = WorkflowComponent::query()
                ->where('key', 'start_trigger')
                ->first(['template']);

            $template = is_array($component?->template) ? $component->template : [];
            $samples = $template['preview_samples'] ?? [];
            $this->componentPreviewSamples = is_array($samples) ? $samples : [];
        }

        $sample = $this->componentPreviewSamples[$event] ?? null;

        return is_array($sample) ? $sample : null;
    }

    /**
     * Generate mock data if no real data exists
     *
     * @return array<string, mixed>
     */
    private function generateMockData(string $event): array
    {
        return match ($event) {
            'customer.created', 'customer.updated' => [
                'id' => 1,
                'name' => '张三',
                'phone' => '13800138000',
                'gender' => 'male',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ],
            'reservation.created', 'reservation.updated' => [
                'id' => 1,
                'customer_id' => 1,
                'date' => now()->toDateString(),
                'time' => now()->format('H:i:s'),
                'status' => 'pending',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ],
            default => [
                'id' => 1,
                'event' => $event,
                'created_at' => now()->toIso8601String(),
            ],
        };
    }
}
