<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPackage extends BaseModel
{
    protected $table = 'product_package';

    protected function casts(): array
    {
        return [
            'amount'    => 'float',
            'editable'  => 'boolean',
            'splitable' => 'boolean',
            'disabled'  => 'boolean',
        ];
    }

    public static function getInfo($id)
    {
        if (!$id) {
            return false;
        }

        static $_info = [];

        if (!isset($_info[$id])) {
            $_info[$id] = static::find($id);
        }

        return $_info[$id];
    }

    /**
     * 创建人
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 类别
     * @return BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ProductPackageType::class);
    }

    /**
     * 明细表
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(ProductPackageDetail::class, 'package_id');
    }
}
