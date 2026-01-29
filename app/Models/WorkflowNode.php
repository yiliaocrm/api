<?php

namespace App\Models;

class WorkflowNode extends BaseModel
{
    protected function casts(): array
    {
        return [
            'dsl' => 'json',
            'template' => 'json',
        ];
    }
}
