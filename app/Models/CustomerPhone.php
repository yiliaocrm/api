<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPhone extends BaseModel
{
    use HasUuids;

    /**
     * 显示原始手机号码
     * @var bool
     */
    public static bool $showOriginalPhone = false;

    /**
     * 隐藏手机号码
     * @return Attribute
     */
    protected function phone(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                // 显示原始号码
                if (static::$showOriginalPhone) {
                    return $value;
                }
                // 隐藏号码
                if (parameter('customer_phone_click2show') || (user() && !user()->hasAnyAccess(['superuser', 'customer.phone']))) {
                    return hide_phone($value);
                }
                return $value;
            },
        );
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
     * 获取此电话号码与顾客的关系。
     */
    public function relationship(): BelongsTo
    {
        return $this->belongsTo(CustomerPhoneRelationship::class, 'relation_id');
    }
}
