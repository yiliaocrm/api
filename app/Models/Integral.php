<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Integral extends BaseModel
{
    protected $table = 'integral';

    protected function casts(): array
    {
        return [
            'data'    => 'array',
            'expired' => 'boolean'
        ];
    }

    /**
     * 顾客信息
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
