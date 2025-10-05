<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class AppointmentConfig extends BaseModel
{
    protected function casts(): array
    {
        return [
            'color_scheme' => 'json',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id', 'id');
    }

    /**
     * 排班
     * @return HasManyThrough
     */
    public function schedules(): HasManyThrough
    {
        return $this->hasManyThrough(Schedule::class, User::class, 'id', 'user_id', 'target_id', 'id');
    }
}
