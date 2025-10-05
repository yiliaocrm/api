<?php

namespace App\Models;

use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLog extends BaseModel
{
    protected $table = 'customer_log';

    protected function casts(): array
    {
        return [
            'original' => 'array',
            'dirty'    => 'array',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($log) {
            $log->action  = Route::currentRouteAction();
            $log->user_id = user() ? user()->id : 0;
        });
    }

    /**
     * 操作人
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
