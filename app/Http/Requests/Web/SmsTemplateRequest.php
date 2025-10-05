<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class SmsTemplateRequest extends FormRequest
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

    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'addCategory' => $this->getAddCategoryRules(),
            'updateCategory' => $this->getUpdateCategoryRules(),
            'removeCategory' => $this->getRemoveCategoryRules(),
            'swapCategory' => $this->getSwapCategoryRules(),
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'enable' => $this->getEnableRules(),
            'disable' => $this->getDisableRules(),
            'remove' => $this->getRemoveRules(),
            default => [],
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'addCategory' => $this->getAddCategoryMessages(),
            'updateCategory' => $this->getUpdateCategoryMessages(),
            'removeCategory' => $this->getRemoveCategoryMessages(),
            'swapCategory' => $this->getSwapCategoryMessages(),
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'enable' => $this->getEnableMessages(),
            'disable' => $this->getDisableMessages(),
            'remove' => $this->getRemoveMessages(),
            default => [],
        };
    }

    /**
     * 添加分类验证规则
     */
    protected function getAddCategoryRules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    protected function getAddCategoryMessages(): array
    {
        return [
            'name.required' => '分类名称不能为空',
            'name.string'   => '分类名称必须为字符串',
            'name.max'      => '分类名称最大长度为255',
        ];
    }

    /**
     * 更新分类验证规则
     */
    protected function getUpdateCategoryRules(): array
    {
        return [
            'id'   => 'required|integer|exists:sms_categories,id',
            'name' => 'required|string|max:255',
        ];
    }

    protected function getUpdateCategoryMessages(): array
    {
        return [
            'id.required'   => '分类ID不能为空',
            'id.integer'    => '分类ID必须为整数',
            'id.exists'     => '分类ID不存在',
            'name.required' => '分类名称不能为空',
            'name.string'   => '分类名称必须为字符串',
            'name.max'      => '分类名称最大长度为255',
        ];
    }

    /**
     * 删除分类验证规则
     */
    protected function getRemoveCategoryRules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:sms_categories,id',
                function ($attribute, $value, $fail) {
                    if (DB::table('sms_templates')->where('category_id', $value)->count()) {
                        $fail('该分类下有短信模板，不能删除');
                    }
                },
            ],
        ];
    }

    protected function getRemoveCategoryMessages(): array
    {
        return [
            'id.required' => '分类ID不能为空',
            'id.integer'  => '分类ID必须为整数',
            'id.exists'   => '分类ID不存在',
        ];
    }

    /**
     * 交换分类顺序验证规则
     */
    protected function getSwapCategoryRules(): array
    {
        return [
            'id1' => 'required|integer|exists:sms_categories,id',
            'id2' => 'required|integer|exists:sms_categories,id',
        ];
    }

    protected function getSwapCategoryMessages(): array
    {
        return [
            'id1.required' => '分类ID不能为空',
            'id1.integer'  => '分类ID必须为整数',
            'id1.exists'   => '分类ID不存在',
            'id2.required' => '分类ID不能为空',
            'id2.integer'  => '分类ID必须为整数',
            'id2.exists'   => '分类ID不存在',
        ];
    }

    /**
     * 启用模板验证规则
     */
    protected function getEnableRules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:sms_templates,id',
                function ($attribute, $value, $fail) {
                    $template = DB::table('sms_templates')->where('id', $value)->first();
                    if (!$template->disabled) {
                        $fail('该模板已经是启用状态');
                    }
                },
            ],
        ];
    }

    protected function getEnableMessages(): array
    {
        return [
            'id.required' => '模板ID不能为空',
            'id.integer'  => '模板ID必须为整数',
            'id.exists'   => '模板ID不存在',
        ];
    }

    /**
     * 禁用模板验证规则
     */
    protected function getDisableRules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:sms_templates,id',
                function ($attribute, $value, $fail) {
                    $template = DB::table('sms_templates')->where('id', $value)->first();
                    if ($template->disabled) {
                        $fail('该模板已经是禁用状态');
                        return;
                    }
                },
            ],
        ];
    }

    protected function getDisableMessages(): array
    {
        return [
            'id.required' => '模板ID不能为空',
            'id.integer'  => '模板ID必须为整数',
            'id.exists'   => '模板ID不存在',
        ];
    }

    /**
     * 删除模板验证规则
     */
    protected function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:sms_templates,id',
            ],
        ];
    }

    protected function getRemoveMessages(): array
    {
        return [
            'id.required' => '模板ID不能为空',
            'id.integer'  => '模板ID必须为整数',
            'id.exists'   => '模板ID不存在',
        ];
    }

    /**
     * 创建模板验证规则
     */
    protected function getCreateRules(): array
    {
        return [
            'category_id' => 'required|integer|exists:sms_categories,id',
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:100',
            'content'     => 'required|string',
            'scenario_id' => 'required|integer|exists:sms_scenarios,id',
            'channel'     => 'required|string|max:50',
        ];
    }

    protected function getCreateMessages(): array
    {
        return [
            'category_id.required' => '模板分类不能为空',
            'category_id.integer'  => '模板分类必须为整数',
            'category_id.exists'   => '模板分类不存在',
            'name.required'        => '模板名称不能为空',
            'name.string'          => '模板名称必须为字符串',
            'name.max'             => '模板名称最大长度为255',
            'code.required'        => '模板编码不能为空',
            'code.string'          => '模板编码必须为字符串',
            'code.max'             => '模板编码最大长度为100',
            'content.required'     => '模板内容不能为空',
            'content.string'       => '模板内容必须为字符串',
            'scenario_id.required' => '使用场景不能为空',
            'scenario_id.integer'  => '使用场景必须为整数',
            'scenario_id.exists'   => '使用场景不存在',
            'channel.required'     => '短信通道不能为空',
            'channel.string'       => '短信通道必须为字符串',
            'channel.max'          => '短信通道最大长度为50',
        ];
    }

    /**
     * 更新模板验证规则
     */
    protected function getUpdateRules(): array
    {
        return [
            'id'          => [
                'required',
                'integer',
                'exists:sms_templates,id',
            ],
            'category_id' => 'required|integer|exists:sms_categories,id',
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:100',
            'content'     => 'required|string',
            'scenario_id' => 'required|integer|exists:sms_scenarios,id',
            'channel'     => 'required|string|max:50',
        ];
    }

    protected function getUpdateMessages(): array
    {
        return [
            'id.required'          => '模板ID不能为空',
            'id.integer'           => '模板ID必须为整数',
            'id.exists'            => '模板ID不存在',
            'category_id.required' => '模板分类不能为空',
            'category_id.integer'  => '模板分类必须为整数',
            'category_id.exists'   => '模板分类不存在',
            'name.required'        => '模板名称不能为空',
            'name.string'          => '模板名称必须为字符串',
            'name.max'             => '模板名称最大长度为255',
            'code.required'        => '模板编码不能为空',
            'code.string'          => '模板编码必须为字符串',
            'code.max'             => '模板编码最大长度为100',
            'content.required'     => '模板内容不能为空',
            'content.string'       => '模板内容必须为字符串',
            'scenario_id.required' => '使用场景不能为空',
            'scenario_id.integer'  => '使用场景必须为整数',
            'scenario_id.exists'   => '使用场景不存在',
            'channel.required'     => '短信通道不能为空',
            'channel.string'       => '短信通道必须为字符串',
            'channel.max'          => '短信通道最大长度为50',
        ];
    }

    /**
     * 表单数据处理
     */
    public function formData(): array
    {
        return [
            'category_id' => $this->input('category_id'),
            'name'        => $this->input('name'),
            'code'        => $this->input('code'),
            'content'     => $this->input('content'),
            'scenario_id' => $this->input('scenario_id'),
            'channel'     => $this->input('channel'),
        ];
    }
}
