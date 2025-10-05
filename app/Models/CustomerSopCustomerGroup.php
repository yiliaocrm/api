<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSopCustomerGroup extends BaseModel
{
    /**
     * 所属旅程
     * @return BelongsTo
     */
    public function customerSop(): BelongsTo
    {
        return $this->belongsTo(CustomerSop::class, 'sop_id');
    }
}
