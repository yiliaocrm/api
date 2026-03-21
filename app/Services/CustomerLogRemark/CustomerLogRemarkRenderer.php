<?php

namespace App\Services\CustomerLogRemark;

use App\Models\CustomerLog;
use App\Services\CustomerLogRemark\Contracts\ValueFormatter;

class CustomerLogRemarkRenderer
{
    public function __construct(
        private readonly CustomerLogRemarkRegistry $registry
    ) {}

    public function render(CustomerLog $log): string
    {
        $original = is_array($log->original) ? $log->original : [];
        $dirty = is_array($log->dirty) ? $log->dirty : [];
        $profile = $this->registry->profileFor($log->logable_type);
        $fields = array_unique(array_merge(array_keys($original), array_keys($dirty)));
        $segments = [];

        foreach ($fields as $field) {
            if ($profile->ignores($field)) {
                continue;
            }

            $hadBefore = array_key_exists($field, $original) && ! $this->isBlank($original[$field]);
            $hasAfter = array_key_exists($field, $dirty) && ! $this->isBlank($dirty[$field]);

            if (! $hadBefore && ! $hasAfter) {
                continue;
            }

            /** @var ValueFormatter $formatter */
            $formatter = app($profile->formatterFor($field));
            $options = $profile->formatterOptionsFor($field);
            $before = $formatter->format($original[$field] ?? null, $options);
            $after = $formatter->format($dirty[$field] ?? null, $options);
            $hadBefore = array_key_exists($field, $original) && ! $this->isBlank($before);
            $hasAfter = array_key_exists($field, $dirty) && ! $this->isBlank($after);

            if ($hadBefore && $hasAfter && $before === $after) {
                continue;
            }

            $segments[] = $this->renderField(
                $profile->labelFor($field),
                $before,
                $after,
                $hadBefore,
                $hasAfter
            );
        }

        return implode('；', $segments);
    }

    private function renderField(string $label, string $before, string $after, bool $hadBefore, bool $hasAfter): string
    {
        if (! $hadBefore && $hasAfter) {
            return "{$label} 设置为{$after}";
        }

        if ($hadBefore && ! $hasAfter) {
            return "{$label} 由{$before}清空";
        }

        return "{$label} 由{$before}变更为{$after}";
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || $value === '';
    }
}
