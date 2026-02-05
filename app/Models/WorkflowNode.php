<?php

namespace App\Models;

class WorkflowNode extends BaseModel
{
    protected function casts(): array
    {
        return [
            'template' => 'json',
            'output_schema' => 'json',
        ];
    }
}
