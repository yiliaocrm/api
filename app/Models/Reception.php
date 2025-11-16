<?php

namespace App\Models;

use App\Enums\ReceptionStatus;
use App\Traits\QueryConditionsTrait;
use App\Observers\ReceptionObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([ReceptionObserver::class])]
class Reception extends BaseModel
{
    use HasUuids;
    use QueryConditionsTrait;

    protected $table = 'reception';

    protected function casts(): array
    {
        return [
            'items'       => 'array',
            'status'      => ReceptionStatus::class,
            'receptioned' => 'boolean',
        ];
    }

    /**
     * 咨询项目表
     * @return BelongsToMany
     */
    public function receptionItems(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'reception_items');
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
     * 顾客咨询项目
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
     * 网电咨询
     * @return HasMany
     */
    public function reservation(): HasMany
    {
        return $this->hasMany(Reservation::class, 'customer_id', 'customer_id');
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
        return $this->hasMany(ReceptionOrder::class, 'reception_id')->orderBy('created_at', 'desc');
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
     * 分诊科室
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * 接诊类型
     * @return BelongsTo
     */
    public function receptionType(): BelongsTo
    {
        return $this->belongsTo(ReceptionType::class, 'type', 'id');
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
     * 录入人员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 接待人员
     * @return BelongsTo
     */
    public function receptionUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reception', 'id');
    }

    /**
     * 分诊顾问
     * @return BelongsTo
     */
    public function consultantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultant', 'id');
    }

    /**
     * 二开人员
     * @return BelongsTo
     */
    public function ekUserRelation(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ek_user', 'id');
    }

    /**
     * 助诊医生
     * @return BelongsTo
     */
    public function doctorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor', 'id');
    }


    /**
     * 预约记录
     * @return HasOne
     */
    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class);
    }

    /**
     * 成交状态
     * @return Attribute
     */
    protected function statusText(): Attribute
    {
        return new Attribute(
            get: fn() => $this->status->getLabel(),
        );
    }
}
