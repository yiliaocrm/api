<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends BaseModel
{
    protected $table = 'schedule';

    protected function casts(): array
    {
        return [
            'onduty' => 'boolean'
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
