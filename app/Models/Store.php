<?php

namespace App\Models;

class Store extends BaseModel
{
    protected function casts(): array
    {
        return [
            'business_start'           => 'datetime:H:i',
            'business_end'             => 'datetime:H:i',
            'appointment_color_config' => 'json',
        ];
    }
}
