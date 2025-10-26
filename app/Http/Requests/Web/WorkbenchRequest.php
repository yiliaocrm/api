<?php

namespace App\Http\Requests\Web;

use App\Models\Reception;
use App\Models\Appointment;
use App\Rules\Web\SceneRule;
use App\Models\ReceptionType;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

class WorkbenchRequest extends FormRequest
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
            'reception' => $this->getReceptionRules(),
            'appointment' => $this->getAppointmentRules(),
            default => [],
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'reception' => $this->getReceptionMessages(),
            'appointment' => $this->getAppointmentMessages(),
            default => []
        };
    }

    /**
     * 获取流水牌数据统计
     * @param string $permission
     * @return int
     */
    public function getMenuCount(string $permission): int
    {
        $todayWorkbench  = Appointment::query()->whereDate('date', today())->count();
        $receptionManage = Reception::query()->whereDate('created_at', today())->count();
        return match ($permission) {
            'workbench.today' => $todayWorkbench,
            'workbench.reception' => $receptionManage,
            default => 0,
        };
    }

    /**
     * 获取分诊接待类型统计
     * @param Builder $builder
     * @return Collection
     */
    public function getReceptionDashboard(Builder $builder): Collection
    {
        // 获取分诊类型统计
        $types  = ReceptionType::query()->orderBy('id')->get();
        $counts = $builder->clone()
            ->select('reception.type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('reception.type')
            ->reorder() // 清除排序，避免影响groupBy
            ->pluck('count', 'type');

        return $types->map(function ($type) use ($counts) {
            return [
                'id'    => $type->id,
                'name'  => $type->name,
                'count' => $counts->get($type->id, 0)
            ];
        });
    }

    private function getReceptionRules(): array
    {
        return [
            'filters'      => [
                'nullable',
                'array',
                new SceneRule('WorkbenchReception')
            ],
            'created_at'   => 'required|array|size:2',
            'created_at.*' => 'required|date|date_format:Y-m-d',
        ];
    }

    private function getReceptionMessages(): array
    {
        return [
            'filters.array'            => '[场景化筛选条件]格式不正确',
            'created_at.required'      => '[查询时间]不能为空',
            'created_at.array'         => '[查询时间]格式不正确',
            'created_at.size'          => '[查询时间]格式不正确',
            'created_at.*.required'    => '[查询时间]格式不正确',
            'created_at.*.date'        => '[查询时间]格式不正确',
            'created_at.*.date_format' => '[查询时间]格式不正确',
        ];
    }

    private function getAppointmentRules(): array
    {
        return [
            'filters'      => [
                'nullable',
                'array',
                new SceneRule('WorkbenchAppointment')
            ],
            'created_at'   => 'required|array|size:2',
            'created_at.*' => 'required|date|date_format:Y-m-d',
        ];
    }

    private function getAppointmentMessages(): array
    {
        return [
            'filters.array'            => '[场景化筛选条件]格式不正确',
            'created_at.required'      => '[查询时间]不能为空',
            'created_at.array'         => '[查询时间]格式不正确',
            'created_at.size'          => '[查询时间]格式不正确',
            'created_at.*.required'    => '[查询时间]格式不正确',
            'created_at.*.date'        => '[查询时间]格式不正确',
            'created_at.*.date_format' => '[查询时间]格式不正确',
        ];
    }
}
