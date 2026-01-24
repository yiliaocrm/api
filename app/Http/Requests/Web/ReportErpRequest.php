<?php

namespace App\Http\Requests\Web;

use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;

class ReportErpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'inventoryDetail' => $this->getInventoryDetailRules(),
            'retailOutboundDetail' => $this->getRetailOutboundDetailRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'inventoryDetail' => $this->getInventoryDetailMessages(),
            'retailOutboundDetail' => $this->getRetailOutboundDetailMessages(),
            default => []
        };
    }

    private function getInventoryDetailRules(): array
    {
        return [
            // 场景化搜索筛选条件
            'filters'    => [
                'nullable',
                'array',
                new SceneRule('ReportInventoryDetail'),
            ],
            // 分页参数
            'rows'       => 'required|integer|min:1|max:100',
            'page'       => 'required|integer|min:1',
            // 排序参数
            'sort'       => 'nullable|string',
            'order'      => 'nullable|string|in:asc,desc',
            // 日期范围（必须）
            'date'       => 'required|array|size:2',
            'date.0'     => 'required|date',
            'date.1'     => 'required|date|after_or_equal:date.0',
            // 商品名称（关键词搜索）
            'goods_name' => 'nullable|string|max:200',
        ];
    }

    private function getInventoryDetailMessages(): array
    {
        return [
            // filters 错误消息
            'filters.array'         => '筛选条件必须为数组',

            // 分页错误消息
            'rows.required'         => '每页条数不能为空',
            'rows.integer'          => '每页条数必须是整数',
            'rows.min'              => '每页条数最少为1',
            'rows.max'              => '每页条数最多为100',
            'page.required'         => '页码不能为空',
            'page.integer'          => '页码必须是整数',
            'page.min'              => '页码至少为1',

            // 排序错误消息
            'sort.string'           => '排序字段必须为字符串',
            'order.string'          => '排序方式必须为字符串',
            'order.in'              => '排序方式只能为asc或desc',

            // 日期范围错误消息
            'date.required'         => '日期范围不能为空',
            'date.array'            => '日期范围必须为数组',
            'date.size'             => '日期范围必须包含开始和结束日期',
            'date.0.required'       => '开始日期不能为空',
            'date.0.date'           => '开始日期格式不正确',
            'date.1.required'       => '结束日期不能为空',
            'date.1.date'           => '结束日期格式不正确',
            'date.1.after_or_equal' => '结束日期不能早于开始日期',

            // 商品名称错误消息
            'goods_name.string'     => '商品名称必须是字符串',
            'goods_name.max'        => '商品名称最多200个字符',
        ];
    }

    private function getRetailOutboundDetailRules(): array
    {
        return [
            // 场景化搜索筛选条件
            'filters' => [
                'nullable',
                'array',
                new SceneRule('ReportRetailOutboundDetail'),
            ],
            // 分页参数
            'rows'    => 'required|integer|min:1|max:100',
            'page'    => 'required|integer|min:1',
            // 排序参数
            'sort'    => 'nullable|string',
            'order'   => 'nullable|string|in:asc,desc',
            // 日期范围（必须）
            'date'    => 'required|array|size:2',
            'date.0'  => 'required|date',
            'date.1'  => 'required|date|after_or_equal:date.0',
        ];
    }

    private function getRetailOutboundDetailMessages(): array
    {
        return [
            // filters 错误消息
            'filters.array'         => '筛选条件必须为数组',

            // 分页错误消息
            'rows.required'         => '每页条数不能为空',
            'rows.integer'          => '每页条数必须是整数',
            'rows.min'              => '每页条数最少为1',
            'rows.max'              => '每页条数最多为100',
            'page.required'         => '页码不能为空',
            'page.integer'          => '页码必须是整数',
            'page.min'              => '页码至少为1',

            // 排序错误消息
            'sort.string'           => '排序字段必须为字符串',
            'order.string'          => '排序方式必须为字符串',
            'order.in'              => '排序方式只能为asc或desc',

            // 出料日期错误消息
            'date.required'         => '出料日期不能为空',
            'date.array'            => '出料日期必须为数组',
            'date.size'             => '出料日期必须包含开始和结束日期',
            'date.0.required'       => '开始日期不能为空',
            'date.0.date'           => '开始日期格式不正确',
            'date.1.required'       => '结束日期不能为空',
            'date.1.date'           => '结束日期格式不正确',
            'date.1.after_or_equal' => '结束日期不能早于开始日期',
        ];
    }
}
