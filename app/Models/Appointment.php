<?php

namespace App\Models;

use App\Enums\AppointmentType;
use App\Enums\AppointmentStatus;
use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends BaseModel
{
    use HasUuids, QueryConditionsTrait;

    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'type'   => AppointmentType::class,
            'items'  => 'array',
            'status' => AppointmentStatus::class
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
     * 顾问
     * @return BelongsTo
     */
    public function consultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultant_id', 'id');
    }

    /**
     * 技师
     * @return BelongsTo
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id', 'id');
    }

    /**
     * 医生
     * @return BelongsTo
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id', 'id');
    }

    /**
     * 创建人
     * @return BelongsTo
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id', 'id');
    }

    /**
     * 科室信息
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    /**
     * 预约诊室
     * @return BelongsTo
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * 分诊信息
     * @return BelongsTo
     */
    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    /**
     * 顾客日志
     * @return BelongsTo
     */
    public function customerLog(): BelongsTo
    {
        return $this->belongsTo(CustomerLog::class);
    }

    /**
     * 获取状态文本
     * @return Attribute
     */
    protected function statusText(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status->getLabel(),
        );
    }

    /**
     * 获取类型文本
     * @return Attribute
     */
    protected function typeText(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->type?->getLabel(),
        );
    }
}
