<?php

namespace App\Http\Requests\Api;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\Room;
use App\Models\Department;
use App\Models\Appointment;
use App\Enums\AppointmentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

class AppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexRules(),
            'create' => $this->getCreateRules(),
            'dashboard' => $this->getDashboardRules(),
            default => []
        };
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexMessages(),
            'create' => $this->getCreateMessages(),
            'dashboard' => $this->getDashboardMessages(),
            default => []
        };
    }

    /**
     * 获取列表视图的验证规则
     *
     * @return array
     */
    private function getIndexRules(): array
    {
        return [
            'date'  => 'required|date_format:Y-m-d',
            'sort'  => 'nullable|string',
            'order' => 'nullable|in:asc,desc',
            'rows'  => 'nullable|integer|min:1',
        ];
    }

    /**
     * 获取列表视图的错误消息
     *
     * @return array
     */
    private function getIndexMessages(): array
    {
        return [
            'date.required'    => '[日期]不能为空!',
            'date.date_format' => '[日期]格式错误!',
            'order.in'         => '[排序方式]错误!',
            'rows.integer'     => '[每页条数]必须为整数!',
            'rows.min'         => '[每页条数]最小为1!',
        ];
    }

    /**
     * 获取创建预约的验证规则
     *
     * @return array
     */
    private function getCreateRules(): array
    {
        $rules = [
            'customer_id'   => 'required|exists:customer,id',
            'type'          => 'required|in:coming,treatment,operation',
            'date'          => 'required|date_format:Y-m-d',
            'start'         => 'required|date_format:Y-m-d H:i:s',
            'end'           => 'required|date_format:Y-m-d H:i:s',
            'department_id' => 'required|exists:department,id',
            'doctor_id'     => 'required|numeric',
            'consultant_id' => 'required|numeric',
            'technician_id' => 'required|numeric',
            'items'         => 'required|array|exists:item,id',
            'room_id'       => 'required|numeric'
        ];

        // 手术预约
        if ($this->input('type') === 'operation') {
            $rules['anaesthesia'] = 'required|in:regional,general';
        }

        return $rules;
    }

    /**
     * 获取创建预约的错误消息
     *
     * @return array
     */
    private function getCreateMessages(): array
    {
        return [
            'customer_id.exists'   => '没有找到顾客信息',
            'type.required'        => '[预约类型]不能为空!',
            'type.in'              => '[预约类型]错误!',
            'date.required'        => '[预约日期]不能为空!',
            'anaesthesia.required' => '[麻醉类型]不能为空!',
            'anaesthesia.in'       => '[麻醉类型]错误!',
            'room_id.required'     => '[预约诊室]不能为空!'
        ];
    }

    /**
     * 获取看板视图的验证规则
     *
     * @return array
     */
    private function getDashboardRules(): array
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

    /**
     * 获取看板视图的错误消息
     *
     * @return array
     */
    private function getDashboardMessages(): array
    {
        return [
            'date.required'        => '请选择日期',
            'date.date_format'     => '日期格式错误',
            'resource_id.required' => '请选择资源类型',
            'resource_id.in'       => '资源类型错误',
            'resources.array'      => 'resources错误',
        ];
    }

    /**
     * 表单数据
     *
     * @return array
     */
    public function formData(): array
    {
        $items      = $this->input('items');
        $items_name = [];

        foreach ($items as $item) {
            $items_name[] = get_item_name($item);
        }

        $data = [
            'type'           => $this->input('type'),
            'customer_id'    => $this->input('customer_id'),
            'date'           => $this->input('date'),
            'start'          => $this->input('start'),
            'end'            => $this->input('end'),
            'duration'       => $this->input('duration'),
            'status'         => 1,
            'items'          => $items,
            'items_name'     => implode('、', $items_name),
            'department_id'  => $this->input('department_id'),
            'doctor_id'      => $this->input('doctor_id'),
            'consultant_id'  => $this->input('consultant_id'),
            'technician_id'  => $this->input('technician_id'),
            'room_id'        => $this->input('room_id'),
            'create_user_id' => user()->id,
            'remark'         => $this->input('remark')
        ];

        // 手术预约
        if ($this->input('type') === 'operation') {
            $data['anaesthesia'] = $this->input('anaesthesia');
        }

        return $data;
    }

    /**
     * 构建资源列表
     *
     * @return array
     */
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

    /**
     * 构建事件列表
     *
     * @return array
     */
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
     *
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

    /**
     * 获取预约看板配置数据
     *
     * @return array
     */
    public function getConfig(): array
    {
        $prefix = DB::getTablePrefix();

        $room = DB::table('room')
            ->select([
                'room.id',
                'room.name',
                'room.department_id',
                DB::raw("COALESCE({$prefix}appointment_configs.order, {$prefix}room.id) as `order`"),
                DB::raw("COALESCE({$prefix}appointment_configs.display, 1) as `display`")
            ])
            ->leftJoin('appointment_configs', function (JoinClause $join) {
                $join->on('appointment_configs.target_id', '=', 'room.id')
                    ->where('appointment_configs.config_type', 'room')
                    ->where('appointment_configs.store_id', store()->id);
            })
            ->orderByDesc('order')
            ->orderByDesc('room.id')
            ->get()
            ->toArray();

        $department = DB::table('department')
            ->select([
                'department.id',
                'department.name',
                DB::raw("COALESCE({$prefix}appointment_configs.order, {$prefix}department.id) as `order`"),
                DB::raw("COALESCE({$prefix}appointment_configs.display, 1) as `display`")
            ])
            ->leftJoin('appointment_configs', function (JoinClause $join) {
                $join->on('appointment_configs.target_id', '=', 'department.id')
                    ->where('appointment_configs.config_type', 'department')
                    ->where('appointment_configs.store_id', store()->id);
            })
            ->orderByDesc('order')
            ->orderByDesc('department.id')
            ->where('department.primary', 1)
            ->get()
            ->toArray();

        $doctor = DB::table('users')
            ->select([
                'users.id',
                'users.name',
                'users.department_id',
                DB::raw("COALESCE({$prefix}appointment_configs.order, {$prefix}users.id) as `order`"),
                DB::raw("COALESCE({$prefix}appointment_configs.display, 1) as `display`")
            ])
            ->join('role_users', 'role_users.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->leftJoin('appointment_configs', function (JoinClause $join) {
                $join->on('appointment_configs.target_id', '=', 'users.id')
                    ->where('appointment_configs.config_type', 'doctor')
                    ->where('appointment_configs.store_id', store()->id);
            })
            ->where('roles.slug', '=', 'doctor')
            ->where('users.banned', '=', 0)
            ->orderByDesc('order')
            ->orderByDesc('users.id')
            ->get()
            ->toArray();

        $consultant = DB::table('users')
            ->select([
                'users.id',
                'users.name',
                'users.department_id',
                DB::raw("COALESCE({$prefix}appointment_configs.order, {$prefix}users.id) as `order`"),
                DB::raw("COALESCE({$prefix}appointment_configs.display, 1) as `display`")
            ])
            ->join('role_users', 'role_users.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->leftJoin('appointment_configs', function (JoinClause $join) {
                $join->on('appointment_configs.target_id', '=', 'users.id')
                    ->where('appointment_configs.config_type', 'consultant')
                    ->where('appointment_configs.store_id', store()->id);
            })
            ->where('roles.slug', '=', 'consultant')
            ->where('users.banned', '=', 0)
            ->orderByDesc('order')
            ->orderByDesc('users.id')
            ->get()
            ->toArray();

        $technician = DB::table('users')
            ->select([
                'users.id',
                'users.name',
                'users.department_id',
                DB::raw("COALESCE({$prefix}appointment_configs.order, {$prefix}users.id) as `order`"),
                DB::raw("COALESCE({$prefix}appointment_configs.display, 1) as `display`")
            ])
            ->join('role_users', 'role_users.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->leftJoin('appointment_configs', function (JoinClause $join) {
                $join->on('appointment_configs.target_id', '=', 'users.id')
                    ->where('appointment_configs.config_type', 'technician')
                    ->where('appointment_configs.store_id', store()->id);
            })
            ->where('roles.slug', '=', 'technician')
            ->where('users.banned', '=', 0)
            ->orderByDesc('order')
            ->orderByDesc('users.id')
            ->get()
            ->toArray();

        return [
            'room'                     => $room,
            'status'                   => AppointmentStatus::options([AppointmentStatus::CANCELLED]),
            'doctor'                   => $doctor,
            'consultant'               => $consultant,
            'department'               => $department,
            'technician'               => $technician,
            'business_start'           => store()->business_start->format('H:i'),
            'business_end'             => store()->business_end->format('H:i'),
            'slot_duration'            => store()->slot_duration,
            'appointment_color_config' => store()->appointment_color_config,
            'appointment_color_scheme' => store()->appointment_color_scheme,
        ];
    }
}
