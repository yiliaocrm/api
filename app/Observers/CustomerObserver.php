<?php

namespace App\Observers;

use App\Models\Customer;

class CustomerObserver
{
    /**
     * 顾客创建后
     * @param Customer $customer
     * @return void
     */
    public function created(Customer $customer): void
    {
        // 写入生命周期
        $customer->cycle()->create([
            'name'        => '创建顾客',
            'customer_id' => $customer->id
        ]);

        // 写入操作日志
        $customer->log()->create([
            'customer_id' => $customer->id
        ]);
    }

    /**
     * 顾客更新后
     * @param Customer $customer
     * @return void
     */
    public function updated(Customer $customer): void
    {
        if ($customer->isDirty()) {
            $dirty    = $customer->getDirty();
            $fields   = array_keys($dirty);
            $original = [];

            foreach ($fields as $field) {
                $original[$field] = $customer->getRawOriginal($field);
            }

            $customer->log()->create([
                'customer_id' => $customer->id,
                'original'    => $original,
                'dirty'       => $dirty
            ]);
        }
    }

    /**
     * 删除顾客后
     * @param Customer $customer
     * @return void
     */
    public function deleted(Customer $customer): void
    {
        $customer->tags()->delete();
        $customer->items()->detach();
        $customer->phones()->delete();
    }
}
