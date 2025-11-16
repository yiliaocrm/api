<?php

namespace App\Http\Requests\Web;

use App\Models\Scene;
use App\Models\SceneField;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class SceneRequest extends FormRequest
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
            'fields', 'lists' => $this->getListRules(),
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'remove' => $this->getRemoveRules(),
            'format' => $this->getFormatRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'fields', 'lists' => $this->getListMessages(),
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            'format' => $this->getFormatMessages(),
            default => []
        };
    }

    public function formData(): array
    {
        $method = request()->route()->getActionMethod();

        if ($method === 'create') {
            return [
                'page'           => $this->input('page'),
                'name'           => $this->input('name'),
                'public'         => 0,
                'config'         => $this->input('config'),
                'type'           => 'user',
                'create_user_id' => user()->id
            ];
        }

        if ($method === 'update') {
            return [
                'name'   => $this->input('name'),
                'config' => $this->input('config')
            ];
        }

        return [];
    }

    private function getListRules(): array
    {
        return [
            'page' => 'required'
        ];
    }

    private function getListMessages(): array
    {
        return [
            'page.required' => 'page参数不能为空!'
        ];
    }

    private function getCreateRules(): array
    {
        return [
            'page'   => 'required|string',
            'name'   => 'required|string',
            'config' => 'required|array'
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'page.required'   => 'page参数不能为空!',
            'name.required'   => 'name参数不能为空!',
            'config.required' => 'config参数不能为空!',
            'config.array'    => 'config参数必须是数组!'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'     => [
                'required',
                'integer',
                'exists:scenes,id',
                function ($attribute, $value, $fail) {
                    $scene = Scene::query()->find($value);
                    if (!$scene) {
                        $fail('搜索场景不存在!');
                        return;
                    }

                    // 检查权限：只有创建者可以更新
                    if ($scene->create_user_id !== user()->id) {
                        $fail('无权限更新此搜索场景!');
                    }
                }
            ],
            'name'   => 'required|string',
            'config' => 'required|array'
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'     => 'id参数不能为空!',
            'id.integer'      => 'id参数必须是整数!',
            'id.exists'       => '搜索场景不存在!',
            'name.required'   => 'name参数不能为空!',
            'config.required' => 'config参数不能为空!',
            'config.array'    => 'config参数必须是数组!'
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $scene = Scene::query()->find($value);
                    if (!$scene) {
                        $fail('搜索场景不存在!');
                        return;
                    }

                    // 检查权限：只有创建者或公共场景的管理员可以删除
                    if ($scene->create_user_id !== user()->id && !$scene->public) {
                        $fail('无权限删除此搜索场景!');
                    }
                }
            ]
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => 'id参数不能为空!',
            'id.integer'  => 'id参数必须是整数!',
            'id.exists'   => '搜索场景不存在!'
        ];
    }

    private function getFormatRules(): array
    {
        return [
            'page'            => 'required',
            'filters'         => 'nullable|array',
            'filters.*.field' => [
                'required',
                'exists:scene_fields,field,page,' . $this->input('page')
            ],
            // 后续补充验证规则
        ];
    }

    private function getFormatMessages(): array
    {
        return [
            'page.required' => 'page参数不能为空!',
            'filters.array' => 'filters参数必须是数组!'
        ];
    }

    /**
     * 格式化筛选条件
     * @param array $filter
     * @return string
     */
    public function formatterText(array $filter): string
    {
        $page          = $this->input('page');
        $field         = SceneField::query()->where('page', $page)->where('field', $filter['field'])->first();
        $operator      = collect($field->operators)->where('value', $filter['operator'])->first();
        $fieldName     = $field->name;
        $operatorText  = $operator['text'];
        $operatorValue = $operator['value'];

        // 处理 is null 和 is not null 操作符（无需 value）
        if (in_array($operatorValue, ['is null', 'is not null'])) {
            return "{$fieldName} {$operatorText}";
        }

        // 处理 in 和 not in 操作符
        if (in_array($operatorValue, ['in', 'not in']) && is_array($filter['value'])) {
            $values = array_map(function ($value) use ($field, $operatorValue) {
                return $this->formatterTextValue($field, $operatorValue, $value);
            }, $filter['value']);
            return "{$fieldName} {$operatorText} " . implode('、', $values);
        }

        // 级联选择器 渲染最后一级的值
        if (is_array($filter['value']) && $field->component == 'cascader') {
            return "{$fieldName} {$operatorText} {$this->formatterTextValue($field, $operatorValue, end($filter['value']))}";
        }

        // between 区间操作符
        if (is_array($filter['value'])) {
            return "{$fieldName} {$operatorText} {$this->formatterTextValue($field, $operatorValue, $filter['value'][0])} ~ {$this->formatterTextValue($field, $operatorValue, $filter['value'][1])}";
        }

        return "{$fieldName} {$operatorText} {$this->formatterTextValue($field, $operatorValue, $filter['value'])}";
    }

    /**
     * 解析筛选条件值
     * @param SceneField $field
     * @param string $operator
     * @param string|null $value
     * @return string|null
     */
    protected function formatterTextValue(SceneField $field, string $operator, ?string $value): ?string
    {
        // 处理特殊操作符
        if (in_array($operator, ['is null', 'is not null'])) {
            return '';
        }

        // 处理select静态数据
        if ($field->component === 'select' && !$field->api) {
            return $this->formatterSelectValue($field, $value);
        }

        // 处理接口请求数据
        $mappings = [
            '/cache/tags'                 => 'tags',
            '/cache/items'                => 'item',
            '/cache/mediums'              => 'medium',
            '/cache/warehouse'            => 'warehouse',
            '/cache/departments'          => 'department',
            '/cache/goods-type'           => 'goods_type',
            '/cache/product-type'         => 'product_type',
            '/cache/reception-type'       => 'reception_type',
            'department-picking-type'     => 'department_picking_types',
            '/cache/product-package-type' => 'product_package_type',
        ];
        foreach ($mappings as $apiPath => $tableName) {
            if (str_contains($field->api, $apiPath)) {
                return DB::table($tableName)->find($value)->name;
            }
        }

        if ($field->component === 'user') {
            return DB::table('users')->find($value)?->name;
        }

        if ($field->component === 'customer') {
            return DB::table('customer')->find($value)?->name;
        }

        return $value;
    }

    /**
     * 格式化筛选条件值
     * @param SceneField $field
     * @param string|null $value
     * @return string
     */
    private function formatterSelectValue(SceneField $field, ?string $value): string
    {
        $params = $field->component_params;
        if (!$params) {
            return (string)$value;
        }
        if (!isset($params['options']) || !is_array($params['options'])) {
            return (string)$value;
        }
        $options = collect($params['options'])->firstWhere('value', $value);
        return $options ? $options['label'] : (string)$value;
    }
}
