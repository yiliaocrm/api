<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutpatientPrescription extends BaseModel
{
    use HasUuids;

    protected $table = 'outpatient_prescription';
    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'diagnosis' => 'array',
            'amount'    => 'float'
        ];
    }

    /**
     * 处方明细
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(OutpatientPrescriptionDetail::class);
    }

    /**
     * 顾客信息
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
