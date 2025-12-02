<?php

namespace App\Http\Requests\Web;

use App\Models\Medium;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class MarketChannelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'remove' => $this->getRemoveRules(),
            'swap' => $this->getSwapRules(),
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
            'create' => $this->getCreateMessages(),
            'remove' => $this->getRemoveMessages(),
            'swap' => $this->getSwapMessages(),
            default => []
        };
    }

    /**
     * 创建渠道验证规则
     *
     * @return array
     */
    private function getCreateRules(): array
    {
        return [
            'form'          => 'required|array',
            'form.name'     => 'required|string|max:255|unique:medium,name',
            'form.parentid' => 'nullable|integer|exists:medium,id',
            'form.user_id'  => 'required|integer|exists:users,id',
        ];
    }

    /**
     * 创建渠道错误消息
     *
     * @return array
     */
    private function getCreateMessages(): array
    {
        return [
            'form.name.required'    => '渠道名称不能为空',
            'form.name.string'      => '渠道名称必须为字符串',
            'form.name.max'         => '渠道名称最大长度为255',
            'form.name.unique'      => '渠道名称已存在',
            'form.parentid.integer' => '父级渠道必须为整数',
            'form.parentid.exists'  => '父级渠道不存在',
            'form.user_id.required' => '渠道负责人不能为空',
            'form.user_id.integer'  => '渠道负责人必须为整数',
            'form.user_id.exists'   => '渠道负责人不存在',
        ];
    }

    /**
     * 更新渠道验证规则
     *
     * @return array
     */
    private function getUpdateRules(): array
    {
        return [
            'id'            => 'required|integer|exists:medium,id',
            'form.name'     => 'required|string|max:255',
            'form.parentid' => [
                'nullable',
                'integer',
                'exists:medium,id',
                function ($attribute, $value, $fail) {
                    if ($value == $this->input('id')) {
                        $fail('父级渠道不能为自己');
                    }
                },
            ],
        ];
    }

    /**
     * 删除渠道验证规则
     *
     * @return array
     */
    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:medium,id',
                function ($attribute, $value, $fail) {
                    if (Medium::query()->where('parentid', $value)->count()) {
                        $fail('该渠道下存在子渠道，无法删除');
                    }

                    if ($value < 10) {
                        $fail('系统数据，不允许删除！');
                    }

                    if (Customer::query()->whereIn('medium_id', Medium::query()->find($value)->getAllChild()->pluck('id'))->count('id')) {
                        $fail('【顾客表】已经使用了该数据，无法直接删除！');
                    }
                },
            ],
        ];
    }

    /**
     * 删除渠道错误消息
     *
     * @return array
     */
    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '渠道ID不能为空',
            'id.exists'   => '渠道不存在',
        ];
    }

    /**
     * 格式化表单数据
     *
     * @return array
     */
    public function formData(): array
    {
        return [
            'name'         => $this->input('form.name'),
            'parentid'     => $this->input('form.parentid') ?? 4,
            'contact'      => $this->input('form.contact'),
            'phone'        => $this->input('form.phone'),
            'address'      => $this->input('form.address'),
            'bank'         => $this->input('form.bank'),
            'bank_account' => $this->input('form.bank_account'),
            'bank_name'    => $this->input('form.bank_name'),
            'rate'         => $this->input('form.rate', 0),
            'user_id'      => $this->input('form.user_id'),
            'remark'       => $this->input('form.remark'),
        ];
    }

    /**
     * 附件数据
     *
     * @param int $medium_id
     * @return array
     */
    public function attachmentData(int $medium_id): array
    {
        $attachments = [];

        foreach ($this->input('attachments', []) as $attachment) {
            $attachments[] = [
                'medium_id'      => $medium_id,
                'name'           => $attachment['name'],
                'thumb'          => $attachment['thumb'],
                'file_path'      => $attachment['file_path'],
                'file_mime'      => $attachment['file_mime'],
                'create_user_id' => user()->id
            ];
        }

        return $attachments;
    }

    /**
     * 交换渠道顺序验证规则
     *
     * @return array
     */
    private function getSwapRules(): array
    {
        return [
            'id1'      => 'required|integer|exists:medium,id',
            'id2'      => 'required|integer|exists:medium,id',
            'position' => 'required|string|in:bottom,top',
        ];
    }

    /**
     * 交换渠道顺序错误消息
     *
     * @return array
     */
    private function getSwapMessages(): array
    {
        return [
            'id1.required'      => '缺少id1参数',
            'id1.integer'       => 'id1必须是整数',
            'id1.exists'        => 'id1不存在',
            'id2.required'      => '缺少id2参数',
            'id2.integer'       => 'id2必须是整数',
            'id2.exists'        => 'id2不存在',
            'position.required' => '缺少position参数',
            'position.string'   => 'position必须是字符串',
            'position.in'       => 'position参数必须是bottom或top',
        ];
    }
}
