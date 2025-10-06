<?php

namespace App\Http\Requests\Web;

use App\Models\Medium;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class MediumRequest extends FormRequest
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
            'swap' => $this->getSwapRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            'swap' => $this->getSwapMessages(),
            default => []
        };
    }

    private function getCreateRules(): array
    {
        return [
            'parentid' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) {
                    // 为空时不验证
                    if (!$value) {
                        return;
                    }
                    if ($value > 1 && $value < 10) {
                        $fail('不能选择系统分类下面!');
                        return;
                    }
                    $medium = Medium::query()->where('id', $value);
                    if (!$medium->exists()) {
                        $fail('父级分类不存在！');
                        return;
                    }
                    if (in_array(4, explode('-', $medium->value('tree')))) {
                        $fail('【市场渠道】下的分类无法操作！');
                    }
                }
            ],
            'name'     => 'required',
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'parentid.required' => '缺少parentid参数',
            'name.required'     => '缺少name参数',
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'       => [
                'required',
                'integer',
                'exists:medium',
                function ($attribute, $value, $fail) {
                    $medium = Medium::query()->where('id', $value);
                    if (in_array(4, explode('-', $medium->value('tree')))) {
                        $fail('【市场渠道】下的分类无法操作！');
                    }
                }
            ],
            'parentid' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) {
                    // 为空时不验证
                    if (!$value) {
                        return;
                    }
                    if ($value > 1 && $value < 10) {
                        $fail('不能移动到系统分类下面!');
                        return;
                    }
                    $medium = Medium::query()->find($this->input('id'));
                    $parent = Medium::query()->find($value);
                    if (!$parent) {
                        $fail('父级分类不存在！');
                        return;
                    }
                    if ($medium->parentid == $value) {
                        return;
                    }
                    if (in_array(4, explode('-', $parent->value('tree')))) {
                        $fail('【市场渠道】下的分类无法操作！');
                        return;
                    }
                    if (in_array($value, Medium::query()->find($this->input('id'))->getAllChild()->pluck('id')->toArray())) {
                        $fail('不能移动到自己的子分类下！');
                    }
                },
            ],
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required' => '缺少id参数',
            'id.exists'   => 'id不存在'
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:medium',
                function ($attribute, $value, $fail) {
                    if ($value < 10) {
                        $fail('系统数据，不允许删除！');
                        return;
                    }

                    // 市场渠道
                    if (in_array(4, explode('-', Medium::query()->where('id', $value)->value('tree')))) {
                        $fail('【市场渠道】下的分类无法操作！');
                        return;
                    }

                    // 顾客表
                    if (DB::table('customer')->whereIn('medium_id', Medium::query()->find($value)->getAllChild()->pluck('id'))->count('id')) {
                        $fail('【顾客表】已经使用了该数据，无法直接删除！');
                        return;
                    }
                }
            ]
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数',
            'id.exists'   => 'id不存在'
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        $actionMethod = request()->route()->getActionMethod();

        // 新增模式：支持批量创建，返回二维数组
        if ($actionMethod === 'create') {
            $names = explode("\n", $this->input('name'));
            $names = array_filter(array_map('trim', $names)); // 去除空行和首尾空格

            $result = [];
            foreach ($names as $name) {
                $result[] = [
                    'name'           => $name,
                    'parentid'       => $this->input('parentid') ?? 0,
                    'create_user_id' => user()->id,
                ];
            }

            return $result;
        }

        // 更新模式：返回一维数组
        return [
            'name'     => $this->input('name'),
            'parentid' => $this->input('parentid') ?? 0,
        ];
    }

    private function getSwapRules(): array
    {
        return [
            'id1'      => 'required|integer|exists:medium,id',
            'id2'      => 'required|integer|exists:medium,id',
            'position' => 'required|string|in:bottom,top',
        ];
    }

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
