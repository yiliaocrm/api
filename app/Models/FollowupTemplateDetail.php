<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowupTemplateDetail extends BaseModel
{
    protected $guarded = [];

    /**
     * 回访角色
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(FollowupRole::class, 'followup_role', 'value');
    }

    /**
     * 回访人员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 回访类型
     * @return BelongsTo
     */
    public function followupType(): BelongsTo
    {
        return $this->belongsTo(FollowupType::class, 'followup_type_id');
    }
}
