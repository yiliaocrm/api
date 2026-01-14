<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Room;
use App\Models\User;
use App\Models\Department;
use App\Models\AppointmentConfig;
use App\Enums\AppointmentStatus;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    /**
     * 获取预约看板配置数据
     * @return array
     */
    public function getConfig(): array
    {
        $storeId = store()->id;
        $prefix  = DB::getTablePrefix();

        // 获取诊室配置
        $room = Room::query()
            ->select([
                'room.id',
                'room.name',
                'room.department_id',
                DB::raw("COALESCE({$prefix}appointment_configs.order, {$prefix}room.id) as `order`"),
                DB::raw("COALESCE({$prefix}appointment_configs.display, 1) as `display`")
            ])
            ->leftJoin('appointment_configs', function ($join) use ($storeId) {
                $join->on('appointment_configs.target_id', '=', 'room.id')
                    ->where('appointment_configs.config_type', 'room')
                    ->where('appointment_configs.store_id', $storeId);
            })
            ->orderByDesc('order')
            ->orderByDesc('room.id')
            ->get()
            ->toArray();

        // 获取科室配置
        $department = Department::query()
            ->select([
                'department.id',
                'department.name',
                DB::raw("COALESCE({$prefix}appointment_configs.order, {$prefix}department.id) as `order`"),
                DB::raw("COALESCE({$prefix}appointment_configs.display, 1) as `display`")
            ])
            ->leftJoin('appointment_configs', function ($join) use ($storeId) {
                $join->on('appointment_configs.target_id', '=', 'department.id')
                    ->where('appointment_configs.config_type', 'department')
                    ->where('appointment_configs.store_id', $storeId);
            })
            ->where('department.primary', 1)
            ->orderByDesc('order')
            ->orderByDesc('department.id')
            ->get()
            ->toArray();

        // 获取医生配置
        $doctor = User::query()
            ->select([
                'users.id',
                'users.name',
                'users.department_id',
                DB::raw("COALESCE({$prefix}appointment_configs.order, {$prefix}users.id) as `order`"),
                DB::raw("COALESCE({$prefix}appointment_configs.display, 1) as `display`")
            ])
            ->join('role_users', 'role_users.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->leftJoin('appointment_configs', function ($join) use ($storeId) {
                $join->on('appointment_configs.target_id', '=', 'users.id')
                    ->where('appointment_configs.config_type', 'doctor')
                    ->where('appointment_configs.store_id', $storeId);
            })
            ->where('roles.slug', 'doctor')
            ->where('users.banned', 0)
            ->orderByDesc('order')
            ->orderByDesc('users.id')
            ->get()
            ->toArray();

        // 获取咨询师配置
        $consultant = User::query()
            ->select([
                'users.id',
                'users.name',
                'users.department_id',
                DB::raw("COALESCE({$prefix}appointment_configs.order, {$prefix}users.id) as `order`"),
                DB::raw("COALESCE({$prefix}appointment_configs.display, 1) as `display`")
            ])
            ->join('role_users', 'role_users.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->leftJoin('appointment_configs', function ($join) use ($storeId) {
                $join->on('appointment_configs.target_id', '=', 'users.id')
                    ->where('appointment_configs.config_type', 'consultant')
                    ->where('appointment_configs.store_id', $storeId);
            })
            ->where('roles.slug', 'consultant')
            ->where('users.banned', 0)
            ->orderByDesc('order')
            ->orderByDesc('users.id')
            ->get()
            ->toArray();

        // 获取技师配置
        $technician = User::query()
            ->select([
                'users.id',
                'users.name',
                'users.department_id',
                DB::raw("COALESCE({$prefix}appointment_configs.order, {$prefix}users.id) as `order`"),
                DB::raw("COALESCE({$prefix}appointment_configs.display, 1) as `display`")
            ])
            ->join('role_users', 'role_users.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->leftJoin('appointment_configs', function ($join) use ($storeId) {
                $join->on('appointment_configs.target_id', '=', 'users.id')
                    ->where('appointment_configs.config_type', 'technician')
                    ->where('appointment_configs.store_id', $storeId);
            })
            ->where('roles.slug', 'technician')
            ->where('users.banned', 0)
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
     * 保存预约配置
     * @param array $data
     * @return void
     */
    public function saveConfig(array $data): void
    {
        $storeId = store()->id;
        $configs = [];
        $types   = ['room', 'doctor', 'consultant', 'technician', 'department'];

        foreach ($types as $type) {
            $items = $data[$type] ?? [];
            foreach ($items as $index => $item) {
                $configs[] = [
                    'config_type' => $type,
                    'target_id'   => $item['id'],
                    'store_id'    => $storeId,
                    'order'       => (count($items) - ($index + 1)),
                    'display'     => $item['display'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
        }

        AppointmentConfig::query()->where('store_id', $storeId)->delete();
        AppointmentConfig::query()->insert($configs);

        store()->update([
            'slot_duration'            => $data['slot_duration'],
            'business_start'           => $data['business_start'],
            'business_end'             => $data['business_end'],
            'appointment_color_scheme' => $data['appointment_color_scheme'],
            'appointment_color_config' => $data['appointment_color_config']
        ]);
    }

    /**
     * 获取预约看板资源数据
     * @param string $view 视图类型: department, room, doctor, consultant, technician
     * @param array $resourceIds 资源ID过滤（可选）
     * @param string|null $start 开始日期（可选，用于获取排班数据）
     * @param string|null $end 结束日期（可选，用于获取排班数据）
     * @return array
     */
    public function getResourcesData(string $view, array $resourceIds = [], ?string $start = null, ?string $end = null): array
    {
        $prefix  = DB::getTablePrefix();
        $storeId = store()->id;
        $default = ['id' => 0, 'title' => '未指定' . $this->getViewLabel($view), 'order' => 99999];

        return match ($view) {
            'room' => $this->getRoomResources($prefix, $storeId, $resourceIds, $default),
            'department' => $this->getDepartmentResources($prefix, $storeId, $resourceIds, $default),
            'doctor', 'consultant', 'technician' => $this->getUserResources($view, $prefix, $storeId, $resourceIds, $start, $end, $default),
            default => [],
        };
    }

    /**
     * 获取科室资源数据
     * @param string $prefix
     * @param int $storeId
     * @param array $resourceIds
     * @param array $default
     * @return array
     */
    private function getDepartmentResources(string $prefix, int $storeId, array $resourceIds, array $default): array
    {
        $query = DB::table('appointment_configs')
            ->select([
                'department.id',
                'department.name as title',
                DB::raw("COALESCE({$prefix}appointment_configs.order, {$prefix}department.id) as `order`")
            ])
            ->leftJoin('department', 'department.id', '=', 'appointment_configs.target_id')
            ->where('appointment_configs.config_type', 'department')
            ->where('appointment_configs.store_id', $storeId)
            ->where('appointment_configs.display', 1)
            ->where('department.primary', 1);

        if (!empty($resourceIds)) {
            $query->whereIn('department.id', $resourceIds);
        }

        return $query->orderByDesc('order')
            ->get()
            ->prepend($default)
            ->toArray();
    }

    /**
     * 获取诊室资源数据
     * @param string $prefix
     * @param int $storeId
     * @param array $resourceIds
     * @param array $default
     * @return array
     */
    private function getRoomResources(string $prefix, int $storeId, array $resourceIds, array $default): array
    {
        $query = DB::table('appointment_configs')
            ->select([
                'room.id',
                'room.name as title',
                DB::raw("COALESCE({$prefix}appointment_configs.order, {$prefix}room.id) as `order`")
            ])
            ->leftJoin('room', 'room.id', '=', 'appointment_configs.target_id')
            ->where('appointment_configs.config_type', 'room')
            ->where('appointment_configs.store_id', $storeId)
            ->where('appointment_configs.display', 1);

        if (!empty($resourceIds)) {
            $query->whereIn('room.id', $resourceIds);
        }

        return $query->orderByDesc('order')
            ->get()
            ->prepend($default)
            ->toArray();
    }

    /**
     * 获取用户资源数据（医生、咨询师、技师）
     * @param string $type
     * @param string $prefix
     * @param int $storeId
     * @param array $resourceIds
     * @param string|null $start
     * @param string|null $end
     * @param array $default
     * @return array
     */
    private function getUserResources(string $type, string $prefix, int $storeId, array $resourceIds, ?string $start, ?string $end, array $default): array
    {
        $query = AppointmentConfig::query()
            ->select([
                'users.id',
                'users.name as title',
                DB::raw("COALESCE({$prefix}appointment_configs.order, {$prefix}users.id) as `order`"),
                'appointment_configs.config_type',
                'appointment_configs.target_id',
            ])
            ->join('users', 'users.id', '=', 'appointment_configs.target_id')
            ->where('appointment_configs.config_type', $type)
            ->where('appointment_configs.store_id', $storeId)
            ->where('appointment_configs.display', 1);

        if (!empty($resourceIds)) {
            $query->whereIn('users.id', $resourceIds);
        }

        // 如果提供了日期范围，加载排班数据
        if ($start && $end) {
            $query->with([
                'schedules' => function ($q) use ($start, $end, $storeId) {
                    $q->whereBetween('start', [
                        Carbon::parse($start),
                        Carbon::parse($end)->endOfDay(),
                    ])->where('store_id', $storeId);
                }
            ]);
        }

        return $query->orderByDesc('order')
            ->get()
            ->prepend($default)
            ->toArray();
    }

    /**
     * 获取视图类型的中文标签
     */
    private function getViewLabel(string $view): string
    {
        return match ($view) {
            'department' => '科室',
            'room' => '诊间',
            'doctor' => '医生',
            'consultant' => '顾问',
            'technician' => '技师',
            default => '资源',
        };
    }
}
