<?php

namespace App\Http\Requests\Web;

use App\Models\Failure;
use App\Models\Reception;
use Illuminate\Foundation\Http\FormRequest;

class FailureRequest extends FormRequest
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
            'create' => [
                'parentid' => [
                    'nullable',
                    'integer',
                    function ($attribute, $value, $fail) {
                        if (!$value) {
                            return;
                        }
                        $failure = Failure::query()->find($value);
                        if (!$failure) {
                            $fail('父级分类不存在！');
                        }
                    }
                ],
                'name'     => 'required'
            ],
            'update' => [
                'id'       => 'required|integer|exists:failure',
                'parentid' => [
                    'nullable',
                    'integer',
                    function ($attribute, $value, $fail) {
                        if (!$value) {
                            return;
                        }
                        $failure = Failure::query()->find($this->input('id'));
                        $parent  = Failure::query()->find($value);
                        if (!$parent) {
                            $fail('父级分类不存在！');
                        }
                        if ($failure->parentid == $value) {
                            return;
                        }
                        if (in_array($this->input('id'), $parent->getAllChild()->pluck('id')->toArray())) {
                            $fail('不能移动到自己的子分类下！');
                        }
                    }
                ],
                'name'     => 'required'
            ],
            'remove' => [
                'id' => [
                    'required',
                    'integer',
                    'exists:failure',
                    function ($attribute, $value, $fail) {
                        if (Reception::whereIn('failure_id', Failure::find($value)->getAllChild()->pluck('id'))->count()) {
                            $fail('【分诊表】已经使用了该数据，无法直接删除！');
                        }
                    }
                ]
            ],
            default => []
        };
    }

    public function formData(): array
    {
        return [
            'name'     => $this->input('name'),
            'parentid' => $this->input('parentid') ?? 0,
        ];
    }
}
