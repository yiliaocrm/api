<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scene extends BaseModel
{
    protected function casts(): array
    {
        return [
            'config' => 'array',
            'public' => 'boolean',
        ];
    }

    /**
     * 创建人员
     * @return BelongsTo
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id');
    }
}
