<?php

namespace App\Models;

class CashierArrearageDetail extends BaseModel
{
    protected $table = 'cashier_arrearage_detail';

    protected function casts(): array
    {
        return [
            'salesman' => 'array',
        ];
    }
}
