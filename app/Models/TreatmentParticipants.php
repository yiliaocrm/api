<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentParticipants extends BaseModel
{
    protected $table = 'treatment_participants';
    public $timestamps = false;

    /**
     * 配台人员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 配台角色
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
