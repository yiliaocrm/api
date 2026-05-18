<?php

namespace App\Observers;

use App\Models\Reception;
use App\Services\ReceptionAppointmentSyncService;
use Illuminate\Support\Carbon;

class ReceptionObserver
{
    private const string APPOINTMENT_REMARK = '前台分诊自动生成预约记录';

    /**
     * 创建分诊记录后
     */
    public function created(Reception $reception): void
    {
        // 创建[咨询项目]
        $reception->receptionItems()->sync(
            $reception->items
        );

        // 同步[顾客咨询项目]
        $this->syncCustomerItems($reception);

        // 写入[分诊日志]
        $reception->customerLog()->create([
            'customer_id' => $reception->customer_id,
        ]);

        // 写入[生命周期]
        $reception->customerLifeCycle()->create([
            'name' => '顾客上门',
            'customer_id' => $reception->customer_id,
        ]);

        // 写入[沟通记录]
        $reception->customerTalk()->create([
            'name' => '咨询情况',
            'customer_id' => $reception->customer_id,
        ]);

        // 更新[网电报单]
        $reception->reservation()->whereNull('reception_id')->update([
            'reception_id' => $reception->id,
            'status' => 2, // 上门
            'cometime' => Carbon::now()->toDateTimeString(),
        ]);

        // 更新[顾客信息]
        $this->updateCustomerByCreated($reception);

        // [更新]或[创建]预约记录
        app(ReceptionAppointmentSyncService::class)->syncArrivedAppointment($reception, self::APPOINTMENT_REMARK);
    }

    /**
     * 更新分诊记录后
     */
    public function updated(Reception $reception): void
    {
        // 更新[咨询项目]
        $reception->receptionItems()->sync(
            $reception->items
        );

        // 同步[顾客咨询项目]
        $this->syncCustomerItems($reception);

        // 写入日志
        if ($reception->isDirty()) {
            $dirty = $reception->getDirty();
            $reception->customerLog()->create([
                'dirty' => $dirty,
                'original' => array_intersect_key($reception->getOriginal(), $dirty),
                'customer_id' => $reception->customer_id,
            ]);
        }
    }

    /**
     * 删除分诊记录后
     */
    public function deleted(Reception $reception): void
    {
        // 取消[网电报单]上门
        $reception->reservation()->where('reception_id', $reception->id)->update([
            'reception_id' => null,
            'status' => 1,
            'cometime' => null,
        ]);

        // 删除[关联项目]
        $reception->receptionItems()->detach();

        // 删除顾客咨询项目
        $reception->customerItems()->delete();

        // 删除[生命周期]
        $reception->customerLifeCycle()->delete();

        // 删除[沟通信息]
        $reception->customerTalk()->delete();

        // 写入日志
        $reception->customerLog()->create([
            'customer_id' => $reception->customer_id,
        ]);

        // 更新[顾客信息]
        $this->updateCustomerByDeleted($reception);

        // 删除关联预约
        $reception->appointment()->delete();
    }

    /**
     * 同步顾客咨询项目
     */
    protected function syncCustomerItems(Reception $reception): void
    {
        // 先删除旧的关联记录
        $reception->customerItems()->delete();

        // 准备新的数据
        $items = $reception->receptionItems->map(fn ($item) => [
            'item_id' => $item->id,
            'customer_id' => $reception->customer_id,
        ]);

        // 批量创建新的关联记录
        if ($items->isNotEmpty()) {
            $reception->customerItems()->createMany($items->all());
        }
    }

    /**
     * 更新顾客信息
     */
    protected function updateCustomerByCreated(Reception $reception): void
    {
        $update = [
            'last_time' => Carbon::now()->toDateTimeString(),
        ];

        // 第一次上门,指定[现场咨询]
        if (! $reception->customer->consultant) {
            $update['consultant'] = $reception->consultant;
        }

        // 更新第一次上门时间
        if (! $reception->customer->first_time) {
            $update['first_time'] = Carbon::now()->toDateTimeString();
        }

        $reception->customer->update($update);
    }

    /**
     * 删除分诊后更新顾客信息
     */
    protected function updateCustomerByDeleted(Reception $reception): void
    {
        $update = [];
        $customer = $reception->customer;

        // 查询是否第一次来
        $count = Reception::query()
            ->where('customer_id', $reception->customer_id)
            ->where('consultant', $reception->consultant)
            ->where('id', '<>', $reception->id)
            ->count();

        if (! $count) {
            $update['consultant'] = null;
        }

        // 反向更新[上门时间]
        if ($customer->first_time == $customer->last_time && $customer->last_time == $reception->created_at) {
            $update['first_time'] = null;
            $update['last_time'] = null;
        }

        $customer->update($update);
    }
}
