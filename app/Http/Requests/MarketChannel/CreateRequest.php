<?php

namespace App\Http\Requests\MarketChannel;

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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'form'          => 'required|array',
            'form.name'     => 'required|string|max:255|unique:medium,name',
            'form.parentid' => 'required|integer|exists:medium,id',
            'form.user_id'  => 'required|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'form.name.required'     => '渠道名称不能为空',
            'form.name.string'       => '渠道名称必须为字符串',
            'form.name.max'          => '渠道名称最大长度为255',
            'form.name.unique'       => '渠道名称已存在',
            'form.parentid.required' => '父级渠道不能为空',
            'form.parentid.integer'  => '父级渠道必须为整数',
            'form.parentid.exists'   => '父级渠道不存在',
            'form.user_id.required'  => '渠道负责人不能为空',
            'form.user_id.integer'   => '渠道负责人必须为整数',
            'form.user_id.exists'    => '渠道负责人不存在',
        ];
    }

    public function formData(): array
    {
        return [
            'name'         => $this->input('form.name'),
            'parentid'     => $this->input('form.parentid'),
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
     * @param $medium_id
     * @return array
     */
    public function attachmentData($medium_id): array
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
}
