<?php

namespace App\Models;

use App\Observers\ReservationObserver;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([ReservationObserver::class])]
class Reservation extends BaseModel
{
    use HasUuids;

    protected $table = 'reservation';

    protected function casts(): array
    {
        return [
            'items' => 'array',
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

    /**
     * 咨询项目
     * @return BelongsToMany
     */
    public function reservationItems(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'reservation_items');
    }

    /**
     * 顾客网电报单对应的咨询项目
     * @return MorphMany
     */
    public function customerItems(): MorphMany
    {
        return $this->morphMany(CustomerItem::class, 'itemable');
    }

    /**
     * 顾客操作日志
     * @return MorphMany
     */
    public function customerLog(): MorphMany
    {
        return $this->morphMany(CustomerLog::class, 'logable');
    }

    /**
     * 生命周期
     * @return MorphMany
     */
    public function customerLifeCycle(): MorphMany
    {
        return $this->morphMany(CustomerLifeCycle::class, 'cycle');
    }

    /**
     * 沟通记录
     * @return MorphMany
     */
    public function customerTalk(): MorphMany
    {
        return $this->morphMany(CustomerTalk::class, 'talk');
    }

    /**
     * 受理类型
     * @return BelongsTo
     */
    public function reservationType(): BelongsTo
    {
        return $this->belongsTo(ReservationType::class, 'type');
    }

    /**
     * 咨询人员
     * @return BelongsTo
     */
    public function reservationAscription(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ascription');
    }

    /**
     * 录单人员(废弃,后续删除)
     * @return BelongsTo
     */
    public function reservationUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 录单人员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 媒介来源
     * @return BelongsTo
     */
    public function medium(): BelongsTo
    {
        return $this->belongsTo(Medium::class, 'medium_id');
    }

    /**
     * 咨询科室
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
