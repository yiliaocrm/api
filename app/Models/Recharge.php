<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\MorphMany;


class Recharge extends BaseModel
{
    use HasUuids;

    protected $table = 'recharge';
    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'salesman' => 'array',
        ];
    }

    /**
     * 收费通知
     * @return MorphMany
     */
    public function cashierable(): MorphMany
    {
        return $this->morphMany(Cashier::class, 'cashierable');
    }
}
