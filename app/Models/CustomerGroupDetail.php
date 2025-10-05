<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerGroupDetail extends BaseModel
{
    /**
     * 没有主键
     * @var null
     */
    protected $primaryKey = null;

    /**
     * 禁用自增
     * @var bool
     */
    public $incrementing = false;

    /**
     * 顾客信息
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
