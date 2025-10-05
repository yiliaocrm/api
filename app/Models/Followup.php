<?php

namespace App\Models;

use Carbon\Carbon;
use App\Observers\FollowupObserver;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([FollowupObserver::class])]
class Followup extends BaseModel
{
    use HasUuids;

    protected $table = 'followup';

    /**
     * 顾客信息
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 顾客操作日志
     * @return MorphMany
     */
    public function log(): MorphMany
    {
        return $this->morphMany(CustomerLog::class, 'logable');
    }

    /**
     * 沟通记录
     * @return MorphMany
     */
    public function talk(): MorphMany
    {
        return $this->morphMany(CustomerTalk::class, 'talk');
    }

    /**
     * 创建人信息
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 提醒人员
     * @return BelongsTo
     */
    public function followupUserInfo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'followup_user');
    }

    /**
     * 执行人员信息
     * @return BelongsTo
     */
    public function executeUserInfo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'execute_user');
    }

    /**
     * 回访工具
     * @return BelongsTo
     */
    public function followupTool(): BelongsTo
    {
        return $this->belongsTo(FollowupTool::class, 'tool');
    }

    /**
     * 回访类别
     * @return BelongsTo
     */
    public function followupType(): BelongsTo
    {
        return $this->belongsTo(FollowupType::class, 'type');
    }

    public function scopeToday($query)
    {
        return $query->where('followup.date', Carbon::today()->toDateString());
    }
}
