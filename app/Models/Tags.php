<?php

namespace App\Models;

use App\Traits\HasTree;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tags extends BaseModel
{
    use HasTree;

    protected $table = 'tags';

    /**
     * 标签关联的客户
     * @return BelongsToMany
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_tags', 'tag_id', 'customer_id');
    }
}
