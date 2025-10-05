<?php

namespace App\Http\Requests\Web;

use App\Models\Reservation;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ReservationTypeRequest extends FormRequest
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
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'remove' => $this->getRemoveRules(),
            default => []
        };
    }

    private function getCreateRules(): array
    {
        return [
            'name' => 'required|unique:reservation_type'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'   => 'required|exists:reservation_type',
            'name' => [
                'required',
                Rule::unique('reservation_type')->ignore($this->input('id')),
            ]
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:reservation_type',
                function ($attribute, $value, $fail) {
                    if (Reservation::query()->where('type', $value)->first()) {
                        $fail('[网电咨询]表中已经使用,无法删除!');
                    }
                }
            ]
        ];
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            default => []
        };
    }

    private function getCreateMessages(): array
    {
        return [
            'name.required' => '[受理类型]不能为空!',
            'name.unique'   => "受理类型《{$this->input('name')}》已存在！"
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'   => '缺少id参数!',
            'id.exists'     => '没有找到id参数',
            'name.required' => '名称不能为空!',
            'name.unique'   => "《{$this->input('name')}》名称已存在!"
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数!',
            'id.exists'   => '没有找到数据!'
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'name'   => $this->input('name'),
            'remark' => $this->input('remark')
        ];
    }
}
