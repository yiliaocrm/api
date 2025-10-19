<?php

namespace App\Http\Requests\Web;

use App\Models\Reception;
use App\Models\Appointment;
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
            'arrival' => $this->getArrivalRules(),
            default => [],
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'arrival' => $this->getArrivalMessages(),
            default => []
        };
    }

    private function getArrivalRules(): array
    {
        return [
            'id' => 'required|uuid|exists:appointments',
        ];
    }

    private function getArrivalMessages(): array
    {
        return [
            'id.required' => '预约ID不能为空',
            'id.uuid'     => '预约ID格式不正确',
            'id.exists'   => '预约记录不存在',
        ];
    }

    /**
     * 获取流水牌数据统计
     * @param string $permission
     * @return int
     */
    public function getDashboardCount(string $permission): int
    {
        $todayWorkbench  = Appointment::query()->whereDate('date', today())->count();
        $receptionManage = Reception::query()->whereDate('created_at', today())->count();
        return match ($permission) {
            'workbench.today' => $todayWorkbench,
            'reception.manage' => $receptionManage,
            default => 0,
        };
    }
}
