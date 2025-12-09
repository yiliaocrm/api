<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CustomerPhoto extends BaseModel
{
    use HasUuids;

    /**
     * 顾客信息
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 顾客日志
     * @return MorphMany
     */
    public function customerLog(): MorphMany
    {
        return $this->morphMany(CustomerLog::class, 'logable');
    }

    /**
     * 相册图片详情
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(CustomerPhotoDetail::class);
    }

    /**
     * 创建人员
     * @return BelongsTo
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id');
    }
}
