<?php

namespace App\Http\Requests\Api;

use Carbon\Carbon;
use App\Models\Appointment;
use App\Enums\AppointmentStatus;
use Illuminate\Support\Facades\DB;
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
            'date'        => 'required|date_format:Y-m-d',
            'sort'        => 'nullable|string',
            'order'       => 'nullable|in:asc,desc',
            'rows'        => 'nullable|integer|min:1',
            'status'      => 'required|array|in:' . implode(',', array_keys(AppointmentStatus::options())),
            'view'        => 'nullable|string|in:department,room,doctor,consultant,technician',
            'resource_id' => 'nullable|array'
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
            'date.required'     => '[日期]不能为空!',
            'date.date_format'  => '[日期]格式错误!',
            'order.in'          => '[排序方式]错误!',
            'rows.integer'      => '[每页条数]必须为整数!',
            'rows.min'          => '[每页条数]最小为1!',
            'status.required'   => '[预约状态]不能为空!',
            'status.array'      => '[预约状态]必须是数组!',
            'status.in'         => '[预约状态]错误!',
            'view.in'           => '[视图类型]错误!',
            'resource_id.array' => '[资源ID]必须是数组!',
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
            'date'   => 'required|date_format:Y-m-d',
            'view'   => 'required|string|in:department,room,doctor,consultant,technician',
            'status' => 'required|array|in:' . implode(',', array_keys(AppointmentStatus::options())),
        ];

        // 科室id
        if ($this->input('view') === 'department') {
            $rules['resource_id']   = 'nullable|array';
            $rules['resource_id.*'] = 'nullable|integer|exists:department,id';
        }

        // 员工id
        if (in_array($this->input('view'), ['doctor', 'consultant', 'technician'])) {
            $rules['resource_id']   = 'nullable|array';
            $rules['resource_id.*'] = 'nullable|integer|exists:users,id';
        }

        // 房间id
        if ($this->input('view') === 'room') {
            $rules['resource_id']   = 'nullable|array';
            $rules['resource_id.*'] = 'nullable|integer|exists:room,id';
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
            'date.required'         => '[预约日期]不能为空!',
            'date.date_format'      => '[预约日期]格式错误!',
            'view.required'         => '[视图类型]不能为空!',
            'view.string'           => '[视图类型]必须是字符串!',
            'view.in'               => '[视图类型]错误!',
            'status.required'       => '[预约状态]不能为空!',
            'status.array'          => '[预约状态]必须是数组!',
            'status.in'             => '[预约状态]错误!',
            'resource_id.array'     => '[资源ID]必须是数组!',
            'resource_id.*.integer' => '[资源ID]必须是整数!',
            'resource_id.*.exists'  => '[资源ID]不存在!',
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
     * 构建事件列表
     *
     * @return array
     */
    public function structEvents(): array
    {
        $keyword       = $this->input('keyword');
        $view          = $this->input('view');
        $resourceIds   = $this->input('resource_id', []);
        $date          = $this->input('date');

        $query = Appointment::query()
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
            ->where('appointments.date', $date)
            ->whereIn('appointments.status', $this->input('status'))
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', '%' . $keyword . '%'));

        // 根据视图类型和资源ID过滤
        if ($view && !empty($resourceIds)) {
            $query->whereIn('appointments.' . $view . '_id', $resourceIds);
        }

        return $query->orderBy('appointments.start', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * 构建状态统计数据
     * @return array
     */
    public function structStatus(): array
    {
        $keyword     = $this->input('keyword');
        $view        = $this->input('view');
        $resourceIds = $this->input('resource_id', []);
        $date        = $this->input('date');

        // 获取所有可用状态选项
        $statusOptions = AppointmentStatus::options([AppointmentStatus::CANCELLED]);

        // 构建基础查询
        $baseQuery = Appointment::query()
            ->select('appointments.status', DB::raw('COUNT(*) as count'))
            ->leftJoin('customer', 'customer.id', '=', 'appointments.customer_id')
            ->where('appointments.date', $date)
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', '%' . $keyword . '%'));

        // 根据视图类型和资源ID过滤
        if ($view && !empty($resourceIds)) {
            $baseQuery->whereIn('appointments.' . $view . '_id', $resourceIds);
        }

        $baseQuery->groupBy('appointments.status');

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
}
