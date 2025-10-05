<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends BaseModel
{

    protected $table = 'product';

    protected function casts(): array
    {
        return [
            'commission'  => 'boolean',
            'deduct'      => 'boolean',
            'integral'    => 'boolean',
            'price'       => 'double',
            'sales_price' => 'double',
            'successful'  => 'boolean'
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::saving(function ($product) {
            $product->keyword = implode(',', parse_pinyin($product->name));
        });

        static::creating(function ($product) {
            $product->disabled = 0;
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
     * 收费项目类别
     * @return BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    /**
     * 结算科室
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * 划扣科室
     * @return BelongsTo
     */
    public function deductDepartmentRelation(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'deduct_department');
    }

    /**
     * 费用类别
     * @return BelongsTo
     */
    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }
}
