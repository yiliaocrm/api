<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseUser extends BaseModel
{

    /**
     * 仓库
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * 员工信息
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
