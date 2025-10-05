<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends BaseModel
{
    protected $table = 'room';


    /**
     * 所属门店
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * 所属科室
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
