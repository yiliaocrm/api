<?php

namespace App\Models;

use App\Traits\QueryConditionsTrait;
use App\Observers\ConsultantObserver;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([ConsultantObserver::class])]
class Consultant extends BaseModel
{
    use HasUuids;
    use QueryConditionsTrait;

    protected $table = 'reception';

    protected function casts(): array
    {
        return [
            'items' => 'array',
        ];
    }

    /**
     * 咨询项目
     * @return BelongsToMany
     */
    public function receptionItems(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'reception_items', 'reception_id');
    }

    /**
     * 顾客咨询项目
     * @return MorphMany
     */
    public function customerItems(): MorphMany
    {
        return $this->morphMany(CustomerItem::class, 'itemable');
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
     * 网电咨询(已废弃)
     * @deprecated
     * @return HasMany
     */
    public function reservation(): HasMany
    {
        return $this->hasMany(Reservation::class, 'customer_id', 'customer_id');
    }

    /**
     * 网电咨询记录
     * @return HasMany
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
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
     * 现场成交单
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(ReceptionOrder::class, 'reception_id');
    }

    /**
     * 收费通知
     * @return MorphMany
     */
    public function cashierable(): MorphMany
    {
        return $this->morphMany(Cashier::class, 'cashierable');
    }

    /**
     * 未成交原因
     * @return BelongsTo
     */
    public function failure(): BelongsTo
    {
        return $this->belongsTo(Failure::class);
    }

    /**
     * 接诊类型
     * @return BelongsTo
     */
    public function receptionType(): BelongsTo
    {
        return $this->belongsTo(ReceptionType::class, 'type');
    }

    /**
     * 咨询科室
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * 媒介来源
     * @return BelongsTo
     */
    public function medium(): BelongsTo
    {
        return $this->belongsTo(Medium::class);
    }

    /**
     * 现场咨询
     * @return BelongsTo
     */
    public function consultantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultant');
    }

    /**
     * 助诊医生
     * @return BelongsTo
     */
    public function doctorInfo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor');
    }

    /**
     * 接待人员
     * @return BelongsTo
     */
    public function receptionInfo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reception');
    }

    /**
     * 录单人员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
