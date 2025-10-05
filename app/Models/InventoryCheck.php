<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InventoryCheck extends BaseModel
{
    protected $guarded = [];

    /**
     * 盘点明细
     * @return BelongsToMany
     */
    public function details(): BelongsToMany
    {
        return $this->belongsToMany(InventoryCheckDetail::class);
    }


}
