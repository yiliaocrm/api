<?php

namespace App\Http\Requests\MarketChannel;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'id'            => 'required|integer|exists:medium,id',
            'form.name'     => 'required|string|max:255',
            'form.parentid' => [
                'required',
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

    public function formData(): array
    {
        return [
            'name'         => $this->input('form.name'),
            'parentid'     => $this->input('form.parentid', 4),
            'contact'      => $this->input('form.contact'),
            'phone'        => $this->input('form.phone'),
            'address'      => $this->input('form.address'),
            'bank'         => $this->input('form.bank'),
            'bank_account' => $this->input('form.bank_account'),
            'bank_name'    => $this->input('form.bank_name'),
            'rate'         => $this->input('form.rate'),
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
