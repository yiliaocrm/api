<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerArrearageDetail extends BaseModel
{
    protected function casts(): array
    {
        return [
            'before'  => 'float',
            'balance' => 'float',
            'after'   => 'float'
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
