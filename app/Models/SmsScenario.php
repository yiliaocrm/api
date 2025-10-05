<?php

namespace App\Models;

class SmsScenario extends BaseModel
{
    protected function casts(): array
    {
        return [
            'variables' => 'json'
        ];
    }
}
