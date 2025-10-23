<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Position extends BaseModel
{
    /**
     * 拥有该岗位的员工（多对多关系）
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_positions', 'position_id', 'user_id')
            ->withTimestamps();
    }
}
