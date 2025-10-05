<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerPhoneRelationship extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    /**
     * 获取拥有此关系的所有电话号码。
     */
    public function customerPhones(): HasMany
    {
        return $this->hasMany(CustomerPhone::class, 'relationship_id');
    }
}
