<?php

namespace App\Models\Admin;

use App\Models\BaseModel;

class TenantLoginBanner extends BaseModel
{
    protected function casts(): array
    {
        return [
            'disabled' => 'boolean',
            'order'    => 'integer',
        ];
    }
}
