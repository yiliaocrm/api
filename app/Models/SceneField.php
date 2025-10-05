<?php

namespace App\Models;

class SceneField extends BaseModel
{
    protected function casts(): array
    {
        return [
            'operators'        => 'array',
            'component_params' => 'array',
        ];
    }
}
