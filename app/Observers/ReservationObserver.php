<?php

namespace App\Observers;

use App\Models\Reservation;

class ReservationObserver
{
    public function created(Reservation $reservation): void
    {
        // 关联[咨询项目]
        $reservation->reservationItems()->sync(
            $reservation->items
        );

        // 创建[生命周期]
        $reservation->customerLifeCycle()->create([
            'name'        => '网电咨询',
            'customer_id' => $reservation->customer_id
        ]);

        // 创建[操作日志]
        $reservation->customerLog()->create([
            'customer_id' => $reservation->customer_id
        ]);

        // 沟通记录
        $reservation->customerTalk()->create([
            'name'        => '网电咨询备注',
            'customer_id' => $reservation->customer_id
        ]);

        // 同步[customer_items]表中[咨询项目]
        $this->syncCustomerItems($reservation);
    }

    public function updated(Reservation $reservation): void
    {
        // 关联[咨询项目]
        $reservation->reservationItems()->sync(
            $reservation->items
        );

        // 写入变动前后日志
        if ($reservation->isDirty()) {
            $dirty = $reservation->getDirty();
            $reservation->customerLog()->create([
                'dirty'       => $dirty,
                'original'    => array_intersect_key($reservation->getOriginal(), $dirty),
                'customer_id' => $reservation->customer_id,
            ]);
        }

        // 同步[customer_items]表中[咨询项目]
        $this->syncCustomerItems($reservation);
    }

    public function deleted(Reservation $reservation): void
    {
        // 删除[关联项目]
        $reservation->reservationItems()->detach();

        // 删除[生命周期]
        $reservation->customerLifeCycle()->delete();

        // 删除[沟通记录]
        $reservation->customerTalk()->delete();

        // 写入[删除日志]
        $reservation->customerLog()->create([
            'customer_id' => $reservation->customer_id
        ]);

        // 删除[customer_items]表中[咨询项目]
        $reservation->customerItems()->delete();
    }

    /**
     * 同步顾客咨询项目
     * @param Reservation $reservation
     */
    protected function syncCustomerItems(Reservation $reservation): void
    {
        // 先删除旧的关联记录
        $reservation->customerItems()->delete();

        // 准备新的数据
        $items = $reservation->reservationItems->map(fn($item) => [
            'item_id'     => $item->id,
            'customer_id' => $reservation->customer_id,
        ]);

        // 批量创建新的关联记录
        if ($items->isNotEmpty()) {
            $reservation->customerItems()->createMany($items->all());
        }
    }
}
