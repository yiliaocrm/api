<?php

namespace App\Models;

class WorkflowConditionField extends BaseModel
{
    protected $table = 'workflow_condition_fields';

    public $timestamps = false;

    protected $casts = [
        'operators' => 'array',
        'component_params' => 'array',
    ];
}
