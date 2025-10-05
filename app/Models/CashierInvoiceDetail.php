<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierInvoiceDetail extends BaseModel
{
    protected static function boot(): void
    {
        parent::boot();
        static::saved(function (CashierInvoiceDetail $detail) {
            self::updateCustomerProductOrGoods($detail);
        });
    }

    /**
     * 更新顾客项目或物品的开票金额
     * @param CashierInvoiceDetail $detail
     * @return void
     */
    private static function updateCustomerProductOrGoods(CashierInvoiceDetail $detail): void
    {
        if ($detail->customer_product_id) {
            $detail->customerProduct->update([
                'invoice_amount' => $detail->invoice_amount
            ]);
        }
        if ($detail->customer_goods_id) {
            $detail->customerGoods->update([
                'invoice_amount' => $detail->invoice_amount
            ]);
        }
    }

    /**
     * 顾客项目明细表
     * @return BelongsTo
     */
    public function customerProduct(): BelongsTo
    {
        return $this->belongsTo(CustomerProduct::class);
    }

    /**
     * 顾客物品明细表
     * @return BelongsTo
     */
    public function customerGoods(): BelongsTo
    {
        return $this->belongsTo(CustomerGoods::class);
    }

    /**
     * 收费项目
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 收费物品
     * @return BelongsTo
     */
    public function goods(): BelongsTo
    {
        return $this->belongsTo(Goods::class);
    }
}
