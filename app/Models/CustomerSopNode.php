<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSopNode extends BaseModel
{
    /**
     * 获取节点类型
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(CustomerSopNodeType::class, 'type_id');
    }

    /**
     * 获取节点所属的旅程
     */
    public function sop(): BelongsTo
    {
        return $this->belongsTo(CustomerSop::class, 'sop_id');
    }
}
