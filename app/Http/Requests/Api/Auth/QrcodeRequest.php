<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class QrcodeRequest extends FormRequest
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
            'uuid' => [
                'required',
                function ($attribute, $uuid, $fail) {
                    if (!cache("qrcode.login.{$uuid}")) {
                        $fail('二维码不存在或者已过期,请重新获取!');
                    }
                }
            ]
        ];
    }


}
