<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerQufriend extends BaseModel
{
    /**
     * 顾客信息
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 亲友信息
     * @return BelongsTo
     */
    public function relatedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'related_customer_id');
    }

    /**
     * 关系信息
     * @return BelongsTo
     */
    public function qufriend(): BelongsTo
    {
        return $this->belongsTo(Qufriend::class, 'qufriend_id');
    }

    /**
     * 创建人信息
     * @return BelongsTo
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id');
    }
}
