<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OutpatientEmr extends BaseModel
{
    use HasUuids;

    protected $table = 'outpatient_emr';
    public $incrementing = false;
    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'diagnosis' => 'array',
        ];
    }
}
