<?php

namespace App\Models;

class WorkflowComponent extends BaseModel
{
    protected function casts(): array
    {
        return [
            'template' => 'json',
            'output_schema' => 'json',
        ];
    }
}
