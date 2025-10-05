<?php

namespace App\Models;

class CustomerGroupField extends BaseModel
{
    protected function casts(): array
    {
        return [
            'operators'        => 'array',
            'component_params' => 'array',
        ];
    }
}
