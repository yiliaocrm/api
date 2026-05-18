<?php

namespace App\Models;

use App\Enums\ReceptionStatus;
use App\Observers\ConsultantObserver;
use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
            'status' => ReceptionStatus::class,
            'receptioned' => 'boolean',
        ];
    }

    /**
     * 咨询项目
     */
    public function receptionItems(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'reception_items', 'reception_id');
    }

    /**
     * 顾客咨询项目
     */
    public function customerItems(): MorphMany
    {
        return $this->morphMany(CustomerItem::class, 'itemable');
    }

    /**
     * 顾客信息
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 顾客操作日志
     */
    public function customerLog(): MorphMany
    {
        return $this->morphMany(CustomerLog::class, 'logable');
    }

    /**
     * 生命周期
     */
    public function customerLifeCycle(): MorphMany
    {
        return $this->morphMany(CustomerLifeCycle::class, 'cycle');
    }

    /**
     * 网电咨询(已废弃)
     *
     * @deprecated
     */
    public function reservation(): HasMany
    {
        return $this->hasMany(Reservation::class, 'customer_id', 'customer_id');
    }

    /**
     * 网电咨询记录
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'customer_id', 'customer_id');
    }

    /**
     * 沟通记录
     */
    public function customerTalk(): MorphMany
    {
        return $this->morphMany(CustomerTalk::class, 'talk');
    }

    /**
     * 现场成交单
     */
    public function orders(): HasMany
    {
        return $this->hasMany(ReceptionOrder::class, 'reception_id');
    }

    /**
     * 收费通知
     */
    public function cashierable(): MorphMany
    {
        return $this->morphMany(Cashier::class, 'cashierable');
    }

    /**
     * 未成交原因
     */
    public function failure(): BelongsTo
    {
        return $this->belongsTo(Failure::class);
    }

    /**
     * 接诊类型
     */
    public function receptionType(): BelongsTo
    {
        return $this->belongsTo(ReceptionType::class, 'type');
    }

    /**
     * 咨询科室
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * 媒介来源
     */
    public function medium(): BelongsTo
    {
        return $this->belongsTo(Medium::class);
    }

    /**
     * 现场咨询
     */
    public function consultantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultant');
    }

    /**
     * 二开人员
     */
    public function ekUserRelation(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ek_user', 'id');
    }

    /**
     * 助诊医生
     */
    public function doctorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor', 'id');
    }

    /**
     * 助诊医生
     */
    public function doctorInfo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor');
    }

    /**
     * 接待人员
     */
    public function receptionUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reception', 'id');
    }

    /**
     * 接待人员
     */
    public function receptionInfo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reception');
    }

    /**
     * 录单人员
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 成交状态
     */
    protected function statusText(): Attribute
    {
        return new Attribute(
            get: fn() => $this->status->getLabel(),
        );
    }
}
