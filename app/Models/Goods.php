<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Goods extends BaseModel
{
    protected $table = 'goods';

    protected function casts(): array
    {
        return [
            'data'             => 'array',
            'is_drug'          => 'boolean',
            'integral'         => 'boolean',
            'commission'       => 'boolean',
            'high_value'       => 'boolean',
            'inventory_amount' => 'float',
            'inventory_number' => 'float',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::saving(function ($goods) {
            $goods->keyword = implode(',', parse_pinyin($goods->name . $goods->short_name . $goods->approval_number)) . $goods->barcode;
        });

        static::deleted(function ($goods) {
            $goods->unit()->delete();
            $goods->alarm()->delete();
        });
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
     * 单位表
     * @return BelongsToMany
     */
    public function unit(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class)->withTimestamps();
    }

    /**
     * 商品单位
     * @return HasMany
     */
    public function units(): HasMany
    {
        return $this->hasMany(GoodsUnit::class);
    }

    /**
     * 商品基本单位
     * @return HasOne
     */
    public function basicUnit(): HasOne
    {
        return $this->hasOne(GoodsUnit::class)->where('basic', 1);
    }

    /**
     * 分仓预警
     * @return BelongsToMany
     */
    public function alarm(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_alarm', 'goods_id', 'warehouse_id')->withTimestamps();
    }

    /**
     * 分仓预警
     * @return HasMany
     */
    public function alarms(): HasMany
    {
        return $this->hasMany(WarehouseAlarm::class);
    }

    /**
     * 商品分类
     * @return BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(GoodsType::class);
    }

    /**
     * 实时库存
     * @return HasMany
     */
    public function inventorys(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * 库存变动明细
     * @return HasMany
     */
    public function inventoryDetail(): HasMany
    {
        return $this->hasMany(InventoryDetail::class);
    }

    /**
     * 库存可用批次
     * @return HasMany
     */
    public function inventoryBatchs(): HasMany
    {
        return $this->hasMany(InventoryBatchs::class);
    }

    /**
     * 费用类别
     * @return BelongsTo
     */
    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    /**
     * 附件多态关联（通过 attachment_uses 表）
     * @return MorphToMany
     */
    public function attachments(): MorphToMany
    {
        return $this->morphToMany(
            Attachment::class,
            'usable',
            'attachment_uses',
            'usable_id',
            'attachment_id'
        )->using(AttachmentUse::class)->withPivot('sort')->withTimestamps()->orderBy('sort');
    }
}
