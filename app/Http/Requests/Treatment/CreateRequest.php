<?php

namespace App\Http\Requests\Treatment;

use App\Models\Role;
use Ramsey\Uuid\Uuid;
use App\Models\CustomerProduct;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'customer_product_id'      => [
                'required',
                function ($attribute, $customer_product_id, $fail) {
                    $customerProduct = CustomerProduct::query()->find($customer_product_id);
                    if (!$customerProduct) {
                        return $fail('没有找到消费项目!');
                    }
                    if ($customerProduct->leftover == 0) {
                        return $fail('[剩余次数]为0,无法划扣!');
                    }
                    if ($customerProduct->leftover < $this->input('form.times')) {
                        return $fail('[剩余次数]小于[划扣次数]');
                    }
                    // 后期判断项目是否过期
                }
            ],
            'form'                     => 'required|array',
            'form.times'               => 'required|numeric|min:1',
            'form.department_id'       => 'required|exists:department,id',
            'participants'             => 'nullable|array',
            'participants.*.user_id'   => 'required|distinct|exists:users,id',
            'participants.*.role_id'   => 'required|exists:roles,id',
            'followup'                 => 'nullable|array',
            'followup.*.date'          => 'required|date_format:Y-m-d',
            'followup.*.title'         => 'required',
            'followup.*.followup_role' => 'required_without:followup.*.user_id',
            'followup.*.user_id'       => 'required_without:followup.*.followup_role'
        ];
    }

    public function messages(): array
    {
        return [
            'customer_product_id.required'              => '缺少customer_product_id参数',
            'form.times.required'                       => '划扣次数不能为空!',
            'form.times.min'                            => '划扣次数最小为1',
            'form.department_id.required'               => '缺少[划扣科室]参数!',
            'participants.*.user_id.distinct'           => '[配台人员]不能重复!',
            'followup.*.followup_role.required_without' => '[回访角色]与[指定人员]必填一个',
            'followup.*.user_id.required_without'       => '[回访角色]与[指定人员]必填一个',
            'followup.*.user_id.exists'                 => ':attribute[指定人员不存在]'
        ];
    }

    /**
     * 划扣信息
     * @param $customerProduct
     * @return array
     */
    public function formData($customerProduct): array
    {
        $price        = (($customerProduct->income + $customerProduct->deposit) / $customerProduct->times) * $this->input('form.times');
        $coupon       = ($customerProduct->coupon / $customerProduct->times) * $this->input('form.times');
        $arrearage    = ($customerProduct->arrearage / $customerProduct->times) * $this->input('form.times');
        $participants = [];

        foreach ($this->input('participants') as $participant) {
            $participants[] = [
                'user_id' => $participant['user_id'],
                'role_id' => $participant['role_id'],
            ];
        }

        return [
            'customer_id'         => $customerProduct->customer_id,
            'customer_product_id' => $customerProduct->id,
            'product_id'          => $customerProduct->product_id,
            'product_name'        => $customerProduct->product_name,
            'package_id'          => $customerProduct->package_id,
            'package_name'        => $customerProduct->package_name,
            'department_id'       => $this->input('form.department_id'),
            'times'               => $this->input('form.times'),
            'price'               => $price,
            'coupon'              => $coupon,
            'arrearage'           => $arrearage,
            'participants'        => $participants,
            'remark'              => $this->input('form.remark'),
            'user_id'             => user()->id,
            'status'              => 1
        ];
    }

    /**
     * 配台人员
     * @return array
     */
    public function participantsData(): array
    {
        $data = [];

        foreach ($this->input('participants') as $participant) {
            $data[] = [
                'user_id' => $participant['user_id'],
                'role_id' => $participant['role_id']
            ];
        }

        return $data;
    }

    /**
     * 划扣业绩
     * @param $treatment
     * @param $reception_type
     * @param $cashier_id
     * @return array
     */
    public function salesPerformanceData($treatment, $reception_type, $cashier_id): array
    {
        $data = [];

        if (!empty($treatment->participants)) {
            foreach ($treatment->participants as $v) {
                $data[] = [
                    'cashier_id'     => $cashier_id,
                    'customer_id'    => $treatment->customer_id,
                    'position'       => 3,  // 项目服务
                    'table_name'     => 'App\Models\Treatment',
                    'table_id'       => $treatment->id,
                    'user_id'        => $v['user_id'],
                    'reception_type' => $reception_type,
                    'package_id'     => $treatment->package_id,
                    'package_name'   => $treatment->package_name,
                    'product_id'     => $treatment->product_id,
                    'product_name'   => $treatment->product_name,
                    'goods_id'       => null,
                    'goods_name'     => null,
                    'payable'        => 0,
                    'income'         => 0,
                    'arrearage'      => $treatment->arrearage,
                    'deposit'        => 0,
                    'amount'         => ($treatment->price * 100) / 100,  // 计提金额
                    'rate'           => 100,
                    'remark'         => get_user_name($treatment->user_id) . '<项目划扣>'
                ];
            }
        }

        return $data;
    }

    /**
     * 插入回访提醒
     * @param $customer
     * @param $participants
     * @return array
     */
    public function followupData($customer, $participants): array
    {
        $data      = [];
        $followups = $this->input('followup');

        foreach ($followups as $followup) {
            $followup_user = null;

            // 指定回访人员
            if ($followup['user_id']) {
                $followup_user = $followup['user_id'];
            }

            // 指定归属现场咨询回访
            if (!$followup_user && $followup['followup_role'] && $followup['followup_role'] == 'consultant' && $customer->consultant) {
                $followup_user = $customer->consultant;
            }

            // 指定归属开发人员
            if (!$followup_user && $followup['followup_role'] && $followup['followup_role'] == 'ascription' && $customer->ascription) {
                $followup_user = $customer->ascription;
            }

            // 指定配台人员回访
            if (!$followup_user && $followup['followup_role']) {
                $role = Role::query()->where('slug', $followup['followup_role'])->first();
                if (!$role) {
                    continue;
                }
                // 找到回访对应的配台(只取第1个)
                $participant = $participants->where('role_id', $role->id)->first();
                if ($participant) {
                    $followup_user = $participant->user_id;
                }
            }


            if (!$followup_user) {
                continue;
            }

            $data[] = [
                'id'            => Uuid::uuid4()->toString(),
                'customer_id'   => $customer->id,
                'type'          => $followup['type_id'],
                'status'        => 1,
                'tool'          => null,
                'title'         => $followup['title'],
                'date'          => $followup['date'],
                'time'          => null,
                'remark'        => null,
                'followup_user' => $followup_user,
                'execute_user'  => null,
                'user_id'       => user()->id,
                'callid'        => null,
                'created_at'    => now()
            ];
        }


        return $data;
    }
}
