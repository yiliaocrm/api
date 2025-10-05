<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class FollowupTemplateRequest extends FormRequest
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
            'createType' => $this->getCreateTypeRules(),
            'updateType' => $this->getUpdateTypeRules(),
            'removeType' => $this->getRemoveTypeRules(),
            'index' => $this->getIndexRules(),
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'remove' => $this->getRemoveRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'createType' => $this->getCreateTypeMessages(),
            'updateType' => $this->getUpdateTypeMessages(),
            'removeType' => $this->getRemoveTypeMessages(),
            'index' => $this->getIndexMessages(),
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            default => []
        };
    }

    private function getCreateTypeRules(): array
    {
        return [
            'parentid' => 'required|exists:followup_template_type,id',
            'name'     => 'required'
        ];
    }

    private function getCreateTypeMessages(): array
    {
        return [
            'parentid.required' => '缺少parentid参数',
            'parentid.exists'   => '找不到分类id'
        ];
    }

    public function typeFormData(): array
    {
        $data = [
            'name' => $this->input('name')
        ];

        // 如果请求方法是 createType
        if ($this->route()->getActionMethod() === 'createType') {
            $data['parentid'] = $this->input('parentid');
        }

        return $data;
    }

    private function getUpdateTypeRules(): array
    {
        return [
            'id'   => 'required|exists:followup_template_type',
            'name' => 'required'
        ];
    }

    private function getUpdateTypeMessages(): array
    {
        return [
            'id.required'   => '缺少id参数',
            'id.exists'     => '找不到数据',
            'name.required' => '分类名称不能为空'
        ];
    }

    private function getRemoveTypeRules(): array
    {
        return [
            'id' => 'required|exists:followup_template_type'
        ];
    }

    private function getRemoveTypeMessages(): array
    {
        return [
            'id.required' => '缺少id参数',
            'id.exists'   => '找不到数据'
        ];
    }

    private function getIndexRules(): array
    {
        return [
            'type_id' => 'required|exists:followup_template_type,id'
        ];
    }

    private function getIndexMessages(): array
    {
        return [
            'type_id.required' => '缺少type_id参数',
            'type_id.exists'   => '找不到分类id'
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => 'required|exists:followup_template'
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数',
            'id.exists'   => '找不到数据'
        ];
    }

    private function getCreateRules(): array
    {
        return [
            'title'                      => 'required',
            'type_id'                    => 'required|exists:followup_template_type,id',
            'details'                    => 'required|array',
            'details.*.followup_type_id' => 'required|exists:followup_type,id',
            'details.*.day'              => 'required|integer',
            'details.*.title'            => 'required|string:max:255',
            'details.*.followup_role'    => 'required_without:details.*.user_id',
            'details.*.user_id'          => 'required_without:details.*.followup_role'
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'title.required'                           => '标题不能为空',
            'type_id.required'                         => '缺少type_id参数',
            'type_id.exists'                           => '找不到分类id',
            'details.required'                         => '缺少details参数',
            'details.array'                            => 'details参数必须是数组',
            'details.*.followup_type_id.required'      => '缺少[回访类型]参数',
            'details.*.followup_type_id.exists'        => '找不到[回访类型]数据',
            'details.*.day.required'                   => '缺少[跟进时间]参数',
            'details.*.day.integer'                    => '[跟进时间]参数必须是整数',
            'details.*.title.required'                 => '缺少[回访主题]参数',
            'details.*.title.string'                   => '[回访主题]参数必须是字符串',
            'details.*.followup_role.required_without' => '[回访角色]和[指定人员]必须选一个',
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'                         => 'required|exists:followup_template',
            'title'                      => 'required',
            'type_id'                    => 'required|exists:followup_template_type,id',
            'details'                    => 'required|array',
            'details.*.followup_type_id' => 'required|exists:followup_type,id',
            'details.*.day'              => 'required|integer',
            'details.*.title'            => 'required|string:max:255',
            'details.*.followup_role'    => 'required_without:details.*.user_id',
            'details.*.user_id'          => 'required_without:details.*.followup_role'
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'                              => '缺少id参数',
            'id.exists'                                => '找不到数据',
            'title.required'                           => '标题不能为空',
            'type_id.required'                         => '缺少type_id参数',
            'type_id.exists'                           => '找不到分类id',
            'details.required'                         => '缺少details参数',
            'details.array'                            => 'details参数必须是数组',
            'details.*.followup_type_id.required'      => '缺少[回访类型]参数',
            'details.*.followup_type_id.exists'        => '找不到[回访类型]数据',
            'details.*.day.required'                   => '缺少[跟进时间]参数',
            'details.*.day.integer'                    => '[跟进时间]参数必须是整数',
            'details.*.title.required'                 => '缺少[回访主题]参数',
            'details.*.title.string'                   => '[回访主题]参数必须是字符串',
            'details.*.followup_role.required_without' => '[回访角色]和[指定人员]必须选一个',
            'details.*.user_id.required_without'       => '[回访角色]和[指定人员]必须选一个',
            'details.*.user_id.exists'                 => '找不到[指定人员]数据',
            'details.*.user_id.integer'                => '[指定人员]参数必须是整数',
        ];
    }

    public function formData(): array
    {
        $data = [
            'title'   => $this->input('title'),
            'type_id' => $this->input('type_id'),
        ];

        // 如果请求方法是 create
        if ($this->route()->getActionMethod() === 'create') {
            $data['user_id'] = user()->id;
        }

        return $data;
    }

    public function detailsData(): array
    {
        $details = $this->input('details');
        $data    = [];
        foreach ($details as $detail) {
            $data[] = [
                'followup_type_id' => $detail['followup_type_id'],
                'day'              => $detail['day'],
                'title'            => $detail['title'],
                'followup_role'    => $detail['followup_role'] ?? null,
                'user_id'          => $detail['user_id'] ?? null
            ];
        }
        return $data;
    }
}
