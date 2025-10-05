<?php

namespace App\Http\Requests\Api;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\Room;
use App\Models\Department;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

class AppointmentDashboardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'date'        => 'required|date_format:Y-m-d',
            'resource_id' => 'required|in:consultant,doctor,department,room,technician',
        ];

        // 科室id
        if ($this->input('resource_id') === 'department') {
            $rules['resources']   = 'nullable|array';
            $rules['resources.*'] = 'nullable|integer';
        }

        // 员工id
        if (in_array($this->input('resource_id'), ['doctor', 'consultant', 'technician'])) {
            $rules['resources']   = 'nullable|array';
            $rules['resources.*'] = 'nullable|integer';
        }

        // 房间id
        if ($this->input('resource_id') === 'room') {
            $rules['resources']   = 'nullable|array';
            $rules['resources.*'] = 'nullable|integer';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'date.required'        => '请选择日期',
            'date.date_format'     => '日期格式错误',
            'resource_id.required' => '请选择资源类型',
            'resource_id.in'       => '资源类型错误',
            'resources.array'      => 'resources错误',
        ];
    }

    public function structResources(): array
    {
        $resources   = $this->input('resources');
        $resource_id = $this->input('resource_id');

        // 科室视图
        if ($resource_id === 'department') {
            $default = ['id' => 0, 'name' => '未指定科室', 'appointment_order' => 99999];
            return Department::query()
                ->select(['id', 'name', 'appointment_order'])
                ->where('appointment_display', 1)
                ->where('primary', 1)
                ->when($resources, fn(Builder $query) => $query->whereIn('id', $resources))
                ->orderByDesc('appointment_order')
                ->orderByDesc('id')
                ->get()
                ->prepend($default)
                ->toArray();
        }

        // 诊间
        if ($resource_id === 'room') {
            $default = ['id' => 0, 'name' => '未指定诊间', 'appointment_order' => 99999];
            return Room::query()
                ->select(['id', 'name', 'appointment_order'])
                ->where('appointment_display', 1)
                ->when($resources, fn(Builder $query) => $query->whereIn('id', $resources))
                ->orderByDesc('appointment_order')
                ->orderByDesc('id')
                ->get()
                ->prepend($default)
                ->toArray();
        }

        // users表
        return $this->getUsersByRole($resource_id, true);
    }

    public function structEvents(): array
    {
        return Appointment::query()
            ->select(['appointments.*'])
            ->with([
                'room:id,name',
                'doctor:id,name',
                'customer:id,idcard,sex,name',
                'consultant:id,name',
                'department:id,name'
            ])
            ->leftJoin('customer', 'customer.id', '=', 'appointments.customer_id')
            ->where('appointments.date', $this->input('date'))
            ->orderBy('appointments.start')
            ->get()
            ->toArray();
    }

    /**
     * 获取角色对应的用户id
     * @param string $slug
     * @param boolean $schedule
     * @return array
     */
    private function getUsersByRole(string $slug, bool $schedule = false): array
    {
        $default = ['id' => 0, 'name' => '未指定人员', 'schedules' => [], 'appointment_order' => 99999];
        $role    = Role::query()->where('slug', $slug)->first();
        $users   = $this->input('resources', []);

        if (!$role) {
            return $default;
        }

        return $role->users()
            ->select([
                'id',
                'name',
                'appointment_order'
            ])
            // 查询排班
            ->when($schedule, function (Builder $builder) {
                $builder->with([
                    'schedules' => function ($query) {
                        $query->whereBetween('start', [
                            Carbon::parse($this->input('date')),
                            Carbon::parse($this->input('date'))->endOfDay(),
                        ])->where('store_id', store()->id);
                    }
                ]);
            })
            ->where('appointment_display', 1)
            ->where('banned', 0)
            ->when($users, fn(Builder $query) => $query->whereIn('id', $users))
            ->orderByDesc('appointment_order')
            ->orderByDesc('id')
            ->get()
            ->makeHidden('pivot')
            ->prepend($default)
            ->toArray();
    }
}
