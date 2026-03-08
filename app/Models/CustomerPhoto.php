<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CustomerPhoto extends BaseModel
{
    use HasUuids;

    /**
     * 顾客信息
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 照片类型
     */
    public function photoType(): BelongsTo
    {
        return $this->belongsTo(CustomerPhotoType::class);
    }

    /**
     * 顾客日志
     */
    public function customerLog(): MorphMany
    {
        return $this->morphMany(CustomerLog::class, 'logable');
    }

    /**
     * 相册图片详情
     */
    public function details(): HasMany
    {
        return $this->hasMany(CustomerPhotoDetail::class);
    }

    /**
     * 创建人员
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id');
    }
}
