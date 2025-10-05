<?php

namespace App\Models;

class ScheduleRule extends BaseModel
{
    protected $table = 'schedule_rule';

    protected function casts(): array
    {
        return [
            'onduty' => 'boolean'
        ];
    }
}
