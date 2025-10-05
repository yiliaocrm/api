<?php

namespace App\Observers;

use App\Models\Consultant;
use Illuminate\Support\Carbon;

class ConsultantObserver
{
    public function created(Consultant $consultant): void
    {
        // 关联[咨询项目]
        $consultant->receptionItems()->sync(
            $consultant->items
        );

        // 同步[顾客咨询项目]
        $this->syncCustomerItems($consultant);

        // 创建[日志]
        $consultant->customerLog()->create([
            'customer_id' => $consultant->customer_id
        ]);

        // 创建[生命周期]
        $consultant->customerLifeCycle()->create([
            'name'        => '现场分诊',
            'customer_id' => $consultant->customer_id
        ]);

        // 创建[沟通记录]
        $consultant->customerTalk()->create([
            'name'        => '咨询情况',
            'customer_id' => $consultant->customer_id
        ]);

        // 更新[网电报单]为已上门
        $consultant->reservations()->whereNull('reception_id')->update([
            'reception_id' => $consultant->id,
            'status'       => 2, // 上门
            'cometime'     => Carbon::now()->toDateTimeString()
        ]);

        // 更新[顾客信息]
        $this->updateCustomerByCreated($consultant);
    }

    public function updated(Consultant $consultant): void
    {
        // 关联[咨询项目]
        $consultant->receptionItems()->sync(
            $consultant->items
        );

        // 同步[顾客咨询项目]
        $this->syncCustomerItems($consultant);

        // 写入日志
        if ($consultant->isDirty()) {
            $dirty = $consultant->getDirty();
            $consultant->customerLog()->create([
                'dirty'       => $dirty,
                'original'    => array_intersect_key($consultant->getOriginal(), $dirty),
                'customer_id' => $consultant->customer_id,
            ]);
        }
    }

    /**
     * 同步顾客咨询项目
     * @param Consultant $consultant
     */
    protected function syncCustomerItems(Consultant $consultant): void
    {
        // 先删除旧的关联记录
        $consultant->customerItems()->delete();

        // 准备新的数据
        $items = $consultant->receptionItems->map(fn($item) => [
            'item_id'     => $item->id,
            'customer_id' => $consultant->customer_id,
        ]);

        // 批量创建新的关联记录
        if ($items->isNotEmpty()) {
            $consultant->customerItems()->createMany($items->all());
        }
    }

    /**
     * 更新顾客信息
     * @param Consultant $consultant
     * @return void
     */
    protected function updateCustomerByCreated(Consultant $consultant): void
    {
        $update = [
            'last_time' => Carbon::now()->toDateTimeString()
        ];

        // 第一次上门,指定[现场咨询]
        if (!$consultant->customer->consultant) {
            $update['consultant'] = $consultant->consultant;
        }

        // 更新第一次上门时间
        if (!$consultant->customer->first_time) {
            $update['first_time'] = Carbon::now()->toDateTimeString();
        }

        $consultant->customer->update($update);
    }
}
