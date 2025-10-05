<?php

namespace App\Http\Requests\Web;

use App\Models\Tags;
use App\Models\CustomerTags;
use Illuminate\Foundation\Http\FormRequest;

class TagsRequest extends FormRequest
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
                        $tags = Tags::query()->find($value);
                        if (!$tags) {
                            $fail('父级分类不存在！');
                        }
                    }
                ],
                'name'     => 'required'
            ],
            'update' => [
                'id'       => 'required|integer|exists:tags',
                'parentid' => [
                    'nullable',
                    'integer',
                    function ($attribute, $value, $fail) {
                        if (!$value) {
                            return;
                        }
                        $tags   = Tags::query()->find($this->input('id'));
                        $parent = Tags::query()->find($value);
                        if (!$parent) {
                            $fail('父级分类不存在！');
                        }
                        if ($tags->parentid == $value) {
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
                    'exists:tags',
                    function ($attribute, $value, $fail) {
                        $tag = CustomerTags::whereIn('tags_id', Tags::find($value)->getAllChild()->pluck('id'))->first();
                        if ($tag) {
                            $fail('标签已经被使用,无法删除!');
                        }
                    }
                ]
            ],
            default => [],
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
