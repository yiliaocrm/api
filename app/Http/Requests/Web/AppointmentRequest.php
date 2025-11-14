<?php

namespace App\Http\Requests\Web;

use Carbon\Carbon;
use App\Models\Appointment;
use App\Enums\AppointmentStatus;
use App\Models\AppointmentConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Foundation\Http\FormRequest;

class AppointmentRequest extends FormRequest
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
        return match (request()->route()->getActionMethod()) {
            default => [],
            'info', 'arrival' => $this->getInfoRule(),
            'create' => $this->getCreateRule(),
            'update' => $this->getUpdateRule(),
            'events' => $this->getEventsRule(),
            'remove' => $this->getRemoveRule(),
            'history' => $this->getHistoryRule(),
            'getSchedule' => $this->getScheduleRule(),
            'saveConfig' => $this->getSaveConfigRule(),
            'drag' => $this->getDragRule(),
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'info', 'arrival' => $this->getInfoMessage(),
            'create' => $this->getCreateMessage(),
            'update' => $this->getUpdateMessage(),
            'events' => $this->getEventsMessage(),
            'remove' => $this->getRemoveMessage(),
            'history' => $this->getHistoryMessage(),
            'saveConfig' => $this->getSaveConfigMessage(),
            'drag' => $this->getDragMessage(),
        };
    }

    protected function getInfoRule(): array
    {
        return [
            'id' => 'required|exists:appointments,id',
        ];
    }

    protected function getInfoMessage(): array
    {
        return [
            'id.required' => '[预约记录]参数不能为空!',
            'id.exists'   => '[预约记录]不存在!'
        ];
    }

    /**
     * 创建预约验证规则
     * @return string[]
     */
    protected function getCreateRule(): array
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
            'room_id'       => 'required|numeric',
            'duration'      => 'required|numeric'
        ];

        // 手术预约
        if ($this->input('type') === 'operation') {
            $rules['anaesthesia'] = 'required|in:regional,general';
        }

        return $rules;
    }

    /**
     * 创建预约验证消息
     * @return string[]
     */
    protected function getCreateMessage(): array
    {
        return [
            'customer_id.exists'     => '没有找到顾客信息',
            'type.required'          => '[预约类型]不能为空!',
            'type.in'                => '[预约类型]错误!',
            'date.required'          => '[预约日期]不能为空!',
            'anaesthesia.required'   => '[麻醉类型]不能为空!',
            'anaesthesia.in'         => '[麻醉类型]错误!',
            'room_id.required'       => '[预约诊室]不能为空!',
            'duration.required'      => '[预约时长]不能为空!',
            'items.required'         => '[预约项目]不能为空!',
            'items.array'            => '[预约项目]错误!',
            'items.exists'           => '[预约项目]不存在!',
            'start.required'         => '[开始时间]不能为空!',
            'start.date_format'      => '[开始时间]错误!',
            'end.required'           => '[结束时间]不能为空!',
            'end.date_format'        => '[结束时间]错误!',
            'department_id.required' => '[科室信息]不能为空!',
            'department_id.exists'   => '[科室信息]不存在!',
            'doctor_id.required'     => '[医生信息]不能为空!',
            'doctor_id.numeric'      => '[医生信息]参数错误!',
            'consultant_id.required' => '[咨询师信息]不能为空!',
            'consultant_id.numeric'  => '[咨询师信息]参数错误!',
            'technician_id.required' => '[技师信息]不能为空!',
            'technician_id.numeric'  => '[技师信息]参数错误!'
        ];
    }

    protected function getUpdateRule(): array
    {
        $rules = [
            'id'            => 'required|exists:appointments',
            'type'          => 'required|in:coming,treatment,operation',
            'date'          => 'required|date_format:Y-m-d',
            'start'         => 'required|date_format:Y-m-d H:i:s',
            'end'           => 'required|date_format:Y-m-d H:i:s',
            'department_id' => 'required|exists:department,id',
            'consultant_id' => 'required|numeric',
            'technician_id' => 'required|numeric',
            'doctor_id'     => 'required|numeric',
            'items'         => 'required|array|exists:item,id',
            'room_id'       => 'required|numeric'
        ];

        // 手术预约
        if ($this->input('type') === 'operation') {
            $rules['anaesthesia'] = 'required|in:regional,general';
        }

        return $rules;
    }

    protected function getUpdateMessage(): array
    {
        return [
            'id.exists'            => '[预约记录]没有找到!',
            'type.required'        => '[预约类型]不能为空!',
            'type.in'              => '[预约类型]错误!',
            'date.required'        => '[预约日期]不能为空!',
            'anaesthesia.required' => '[麻醉类型]不能为空!',
            'anaesthesia.in'       => '[麻醉类型]错误!',
            'room_id.exists'       => '[预约诊室]不存在!'
        ];
    }

    /**
     * 保存门店配置验证规则
     * @return array
     */
    protected function getSaveConfigRule(): array
    {
        return [
            'room'                     => 'nullable|array',
            'doctor'                   => 'nullable|array',
            'consultant'               => 'nullable|array',
            'technician'               => 'nullable|array',
            'departments'              => 'nullable|array',
            'slot_duration'            => 'required|numeric',
            'business_start'           => 'required',
            'business_end'             => 'required',
            'appointment_color_scheme' => 'required|in:default,classic,custom',
            'appointment_color_config' => 'required|array'
        ];
    }

    /**
     * 保存门店配置验证消息
     * @return string[]
     */
    protected function getSaveConfigMessage(): array
    {
        return [
            'slot_duration.required'            => '[预约间隔]不能为空!',
            'business_start.required'           => '[营业开始时间]不能为空!',
            'business_end.required'             => '[营业结束时间]不能为空!',
            'appointment_color_scheme.required' => '[预约配色方案]不能为空!',
            'appointment_color_scheme.in'       => '[预约配色方案]类型错误!',
            'appointment_color_config.required' => '[预约颜色配置]不能为空!',
            'appointment_color_config.array'    => '[预约颜色配置]类型错误!'
        ];
    }

    protected function getEventsRule(): array
    {
        return [
            'resource_id'   => 'required|in:consultant,doctor,department,room,technician',
            'start'         => 'required|date_format:Y-m-d',
            'end'           => 'required|date_format:Y-m-d',
            'status'        => 'required|array|in:' . implode(',', array_keys(AppointmentStatus::options())),
            'department_id' => 'required|numeric'
        ];
    }

    protected function getScheduleRule(): array
    {
        return [
            'view'        => 'required|in:consultant,doctor,technician,room,department',
            'date'        => 'required|date_format:Y-m-d',
            'resource_id' => 'required',
            'id'          => 'nullable|exists:appointments'
        ];
    }

    protected function getRemoveRule(): array
    {
        return [
            'id' => [
                'required',
                'exists:appointments,id',
                // 后续需要加入更多删除判断规则
            ]
        ];
    }

    private function getHistoryRule(): array
    {
        return [
            'customer_id' => 'required|exists:customer,id',
        ];
    }

    private function getHistoryMessage(): array
    {
        return [
            'customer_id.required' => '[顾客信息]不能为空!',
            'customer_id.exists'   => '[顾客信息]不存在!'
        ];
    }

    /**
     * 预约看板验证消息
     * @return string[]
     */
    protected function getEventsMessage(): array
    {
        return [
            'resource_id.required'   => '[预约看板]参数不能为空!',
            'resource_id.in'         => '[预约看板]参数错误!',
            'start.required'         => '[开始时间]不能为空!',
            'start.date_format'      => '[开始时间]错误!',
            'end.required'           => '[结束时间]不能为空!',
            'end.date_format'        => '[结束时间]错误!',
            'status.required'        => '[状态]不能为空!',
            'status.array'           => '[状态]错误!',
            'status.in'              => '[状态]错误!',
            'department_id.required' => '[科室信息]不能为空!',
            'department_id.numeric'  => '[科室信息]参数错误!'
        ];
    }

    /**
     * 删除预约验证消息
     * @return string[]
     */
    protected function getRemoveMessage(): array
    {
        return [
            'id.required' => '[预约记录]参数不能为空!',
            'id.exists'   => '[预约记录]不存在!'
        ];
    }

    public function structResources(): array
    {
        $prefix      = DB::getTablePrefix();
        $resource_id = $this->input('resource_id');

        // 科室视图
        if ($resource_id === 'department') {
            $default = ['id' => 0, 'title' => '未指定科室', 'order' => 99999];
            return DB::table('appointment_configs')
                ->select([
                    'department.id',
                    'department.name as title',
                    DB::raw("COALESCE({$prefix}appointment_configs.order, {$prefix}department.id) as `order`")
                ])
                ->leftJoin('department', 'department.id', '=', 'appointment_configs.target_id')
                ->where('appointment_configs.config_type', 'department')
                ->where('appointment_configs.store_id', store()->id)
                ->where('appointment_configs.display', 1)
                ->where('department.primary', 1)
                ->orderByDesc('order')
                ->get()
                ->prepend($default)
                ->toArray();
        }

        // 诊间
        if ($resource_id === 'room') {
            $default = ['id' => 0, 'title' => '未指定诊间', 'order' => 99999];
            return DB::table('appointment_configs')
                ->select([
                    'room.id',
                    'room.name as title',
                    DB::raw("COALESCE({$prefix}appointment_configs.order, {$prefix}room.id) as `order`")
                ])
                ->leftJoin('room', 'room.id', '=', 'appointment_configs.target_id')
                ->where('appointment_configs.config_type', 'room')
                ->where('appointment_configs.store_id', store()->id)
                ->where('appointment_configs.display', 1)
                ->orderByDesc('order')
                ->get()
                ->prepend($default)
                ->toArray();
        }

        // 医生、咨询师、技师
        if (in_array($resource_id, ['doctor', 'consultant', 'technician'])) {
            $default = ['id' => 0, 'title' => '未指定人员', 'order' => 99999];
            return AppointmentConfig::query()
                ->with([
                    'schedules' => function ($query) {
                        $query->whereBetween('start', [
                            Carbon::parse($this->input('start')),
                            Carbon::parse($this->input('end'))->endOfDay(),
                        ])
                            ->where('store_id', store()->id);
                    }
                ])
                ->select([
                    'users.id',
                    'users.name as title',
                    DB::raw("COALESCE({$prefix}appointment_configs.order, {$prefix}users.id) as `order`"),
                    'appointment_configs.config_type',
                    'appointment_configs.target_id',
                ])
                ->join('users', 'users.id', '=', 'appointment_configs.target_id')
                ->where('appointment_configs.config_type', $resource_id)
                ->where('appointment_configs.store_id', store()->id)
                ->where('appointment_configs.display', 1)
                ->orderByDesc('order')
                ->get()
                ->prepend($default)
                ->toArray();
        }

        return [];
    }

    /**
     * 预约事件数据
     * @return array
     */
    public function structEvents(): array
    {
        $keyword       = $this->input('keyword');
        $resource_id   = $this->input('resource_id');
        $department_id = $this->input('department_id', 0);
        return Appointment::query()
            ->select('appointments.*')
            ->with([
                'room:id,name',
                'doctor:id,name',
                'customer:id,idcard,sex,name,file_number,birthday,ascription,consultant,remark',
                'customer.ascriptionUser:id,name',
                'customer.consultantUser:id,name',
                'customer.phones',
                'consultant:id,name',
                'department:id,name',
                'createUser:id,name',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'appointments.customer_id')
            ->whereBetween('appointments.date', [
                Carbon::parse($this->input('start')),
                Carbon::parse($this->input('end'))->endOfDay(),
            ])
            ->whereIn('appointments.status', $this->input('status'))
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', '%' . $keyword . '%'))
            ->when($department_id, fn(Builder $query) => $query->where('appointments.department_id', $department_id))
            ->orderBy('appointments.created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * 构建状态统计数据
     * @return array
     */
    public function structStatus(): array
    {
        $keyword       = $this->input('keyword');
        $department_id = $this->input('department_id', 0);
        $status        = $this->input('status');

        // 获取所有可用状态选项
        $statusOptions = AppointmentStatus::options([AppointmentStatus::CANCELLED]);

        // 构建基础查询
        $baseQuery = Appointment::query()
            ->select('appointments.status', DB::raw('COUNT(*) as count'))
            ->leftJoin('customer', 'customer.id', '=', 'appointments.customer_id')
            ->whereBetween('appointments.date', [
                Carbon::parse($this->input('start')),
                Carbon::parse($this->input('end'))->endOfDay(),
            ])
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', '%' . $keyword . '%'))
            ->when($department_id, fn(Builder $query) => $query->where('appointments.department_id', $department_id))
            ->groupBy('appointments.status');

        // 获取状态统计数据
        $statusCounts = $baseQuery->pluck('count', 'status')->toArray();

        // 构建返回结果
        $result = [];
        foreach ($statusOptions as $statusValue => $statusLabel) {
            $result[] = [
                'value' => $statusValue,
                'label' => $statusLabel,
                'count' => $statusCounts[$statusValue] ?? 0,
            ];
        }

        return $result;
    }

    /**
     * 获取预约配置
     * @return array
     */
    public function getConfigData(): array
    {
        $prefix = DB::getTablePrefix();
        $room   = DB::table('room')
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

    /**
     * 保存门店配置
     * @return void
     */
    public function saveConfig(): void
    {
        $data  = [];
        $types = ['room', 'doctor', 'consultant', 'technician', 'department'];

        foreach ($types as $type) {
            $items = $this->input($type, []);
            foreach ($items as $index => $item) {
                $data[] = [
                    'config_type' => $type,
                    'target_id'   => $item['id'],
                    'store_id'    => store()->id,
                    'order'       => (count($items) - ($index + 1)),
                    'display'     => $item['display'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
        }

        DB::table('appointment_configs')->where('store_id', store()->id)->delete();
        DB::table('appointment_configs')->insert($data);

        store()->update([
            'slot_duration'            => $this->input('slot_duration'),
            'business_start'           => $this->input('business_start'),
            'business_end'             => $this->input('business_end'),
            'appointment_color_scheme' => $this->input('appointment_color_scheme'),
            'appointment_color_config' => $this->input('appointment_color_config')
        ]);
    }

    public function formData(): array
    {
        $items      = $this->input('items');
        $items_name = [];

        foreach ($items as $item) {
            $items_name[] = get_item_name($item);
        }

        $data = [
            'type'          => $this->input('type'),
            'date'          => $this->input('date'),
            'start'         => $this->input('start'),
            'end'           => $this->input('end'),
            'duration'      => $this->input('duration'),
            'status'        => 1,
            'items'         => $items,
            'items_name'    => implode('、', $items_name),
            'department_id' => $this->input('department_id'),
            'doctor_id'     => $this->input('doctor_id'),
            'consultant_id' => $this->input('consultant_id'),
            'technician_id' => $this->input('technician_id'),
            'room_id'       => $this->input('room_id'),
            'remark'        => $this->input('remark')
        ];

        // 创建预约
        if (request()->route()->getActionMethod() === 'create') {
            $data['customer_id']    = $this->input('customer_id');
            $data['create_user_id'] = user()->id;
        }

        // 手术预约
        if ($this->input('type') === 'operation') {
            $data['anaesthesia'] = $this->input('anaesthesia');
        }

        return $data;
    }

    /**
     * 拖拽更新预约验证规则
     * @return array
     */
    protected function getDragRule(): array
    {
        $rules = [
            'id'          => 'required|exists:appointments,id',
            'start'       => 'required|date_format:H:i',
            'end'         => 'required|date_format:H:i',
            'date'        => 'nullable|date_format:Y-m-d',
            'resource_id' => 'required|string|in:consultant_id,doctor_id,technician_id,department_id,room_id',
            'target_id'   => 'required|numeric',
        ];

        // 根据 resource_id 的值，对 target_id 进行对应的存在性验证
        $resourceId = $this->input('resource_id');
        if ($resourceId === 'department_id') {
            $rules['target_id'] = 'required|numeric|exists:department,id';
        } elseif ($resourceId === 'room_id') {
            $rules['target_id'] = 'required|numeric|exists:room,id';
        } elseif (in_array($resourceId, ['consultant_id', 'doctor_id', 'technician_id'])) {
            $rules['target_id'] = 'required|numeric|exists:users,id';
        }

        return $rules;
    }

    /**
     * 拖拽更新预约验证消息
     * @return array
     */
    protected function getDragMessage(): array
    {
        return [
            'id.required'          => '[预约记录]参数不能为空!',
            'id.exists'            => '[预约记录]不存在!',
            'start.required'       => '[开始时间]不能为空!',
            'start.date_format'    => '[开始时间]格式错误,应为HH:MM格式!',
            'end.required'         => '[结束时间]不能为空!',
            'end.date_format'      => '[结束时间]格式错误,应为HH:MM格式!',
            'date.date_format'     => '[预约日期]格式错误!',
            'resource_id.required' => '[资源类型]参数不能为空!',
            'resource_id.string'   => '[资源类型]参数错误!',
            'resource_id.in'       => '[资源类型]参数必须是consultant_id、doctor_id、technician_id、department_id或room_id!',
            'target_id.required'   => '[目标ID]参数不能为空!',
            'target_id.numeric'    => '[目标ID]参数错误!',
            'target_id.exists'     => '[目标资源]不存在!',
        ];
    }

    /**
     * 组装拖拽更新数据
     * @return array
     */
    public function dragData(): array
    {
        // 获取原预约记录
        $appointment = Appointment::query()->find($this->input('id'));

        // 如果传入了日期参数则使用，否则使用原预约的日期
        $date = $this->input('date', $appointment->date);

        // 组装完整的开始和结束时间
        $startTime = $this->input('start');
        $endTime   = $this->input('end');

        $start = Carbon::parse($date . ' ' . $startTime);
        $end   = Carbon::parse($date . ' ' . $endTime);

        // 计算预约时长（分钟）
        $duration = $start->diffInMinutes($end);

        // 更新数据
        $updateData = [
            'date'     => $start->format('Y-m-d'),
            'start'    => $start->format('Y-m-d H:i:s'),
            'end'      => $end->format('Y-m-d H:i:s'),
            'duration' => $duration,
        ];

        // 根据 resource_id 更新对应的资源字段
        $resourceId = $this->input('resource_id');
        $targetId   = $this->input('target_id');

        $updateData[$resourceId] = $targetId;

        return $updateData;
    }
}
