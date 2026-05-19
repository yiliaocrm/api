<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Models\Consultant;
use App\Models\Reception;
use Illuminate\Support\Carbon;

class ReceptionAppointmentSyncService
{
    /**
     * 将分诊或咨询记录同步为今日工作中的已到店预约。
     */
    public function syncArrivedAppointment(Reception|Consultant $record, string $remark): Appointment
    {
        if ($record->appointment_id) {
            $appointment = Appointment::query()->find($record->appointment_id);
            $appointment->update($this->existingAppointmentData($record));

            return $appointment;
        }

        $appointment = Appointment::query()->create(
            $this->newAppointmentData($record, $remark)
        );

        $record->appointment_id = $appointment->id;
        $record->saveQuietly();

        return $appointment;
    }

    /**
     * 生成更新已有预约所需的数据，保留已存在的到店时间。
     *
     * @return array<string, mixed>
     */
    private function existingAppointmentData(Reception|Consultant $record): array
    {
        $data = [
            'status' => AppointmentStatus::ARRIVED,
            'reception_id' => $record->id,
            'reception_time' => Carbon::now(),
        ];

        if (! $record->appointment?->arrival_time) {
            $data['arrival_time'] = Carbon::now();
        }

        return $data;
    }

    /**
     * 生成自动创建今日面诊预约所需的数据。
     *
     * @return array<string, mixed>
     */
    private function newAppointmentData(Reception|Consultant $record, string $remark): array
    {
        $store = store();
        $slotDuration = $store->slot_duration;
        $itemsName = collect($record->items ?? [])
            ->map(fn ($item) => get_item_name($item))
            ->implode(',');

        return [
            'customer_id' => $record->customer_id,
            'reception_id' => $record->id,
            'date' => Carbon::today()->toDateString(),
            'start' => $record->created_at,
            'end' => Carbon::now()->addMinutes($slotDuration)->format('Y-m-d H:i:s'),
            'duration' => $slotDuration,
            'reception_time' => $record->created_at,
            'arrival_time' => $record->created_at,
            'status' => AppointmentStatus::ARRIVED,
            'type' => AppointmentType::COMING,
            'items' => $record->items,
            'items_name' => $itemsName,
            'department_id' => $record->department_id,
            'technician_id' => 0,
            'room_id' => 0,
            'doctor_id' => $record->doctor,
            'consultant_id' => $record->consultant,
            'create_user_id' => $record->user_id,
            'remark' => $remark,
        ];
    }
}
