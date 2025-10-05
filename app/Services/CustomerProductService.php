<?php

namespace App\Services;

use App\Models\CustomerProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CustomerProductService
{
    /**
     * 创建购买项目
     * @param array $data
     * @return Builder|Model
     */
    public function create(array $data)
    {
        return CustomerProduct::query()->create($data);
    }

    /**
     * 删除已购项目
     * @param string $id
     * @return void
     */
    public function remove(string $id)
    {
        $product = CustomerProduct::query()->find($id);

        // 写入日志
        $product->customer->log()->create([
            'customer_id' => $product->customer_id,
            'original'    => $product
        ]);

        // 删除
        $product->delete();
    }
}
