<?php

namespace App\Http\Requests;

use App\Models\Item;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ReceptionItems;
use App\Models\ReservationItems;
use Illuminate\Foundation\Http\FormRequest;

class ProductTypeRequest extends FormRequest
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
            'move' => $this->getMoveRules(),
            default => []
        };
    }

    private function getCreateRules(): array
    {
        return [
            'parentid' => 'required|exists:product_type,id',
            'name'     => 'required'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'   => 'required|exists:product_type',
            'name' => 'required'
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:product_type',
                function ($attribute, $value, $fail) {
                    // 判断[收费项目分类]是否有下级数据
                    if (Product::query()->whereIn('type_id', ProductType::query()->find($value)->getAllChild()->pluck('id'))->count()) {
                        $fail('分类下有项目无法删除');
                    }
                    // 如果开启[咨询项目]与[收费项目分类]一致,需要判断[咨询项目]
                    if (parameter('cywebos_enable_item_product_type_sync')) {
                        $items = Item::query()->find($value)->getAllChild()->pluck('id');

                        // 网电咨询项目表
                        if (ReservationItems::query()->whereIn('item_id', $items)->count('reservation_id')) {
                            $fail('【网电咨询】已经使用了该数据，无法直接删除！');
                        }

                        // 分诊接待|现场咨询
                        if (ReceptionItems::query()->whereIn('item_id', $items)->count('reception_id')) {
                            $fail('【分诊接待】已经使用了该数据，无法直接删除！');
                        }
                    }
                }
            ]
        ];
    }

    private function getMoveRules(): array
    {
        return [
            'id'       => 'required|exists:product_type|not_in:1',
            'parentid' => [
                'required',
                function ($attribute, $parentid, $fail) {
                    $parent = ProductType::query()->find($parentid);
                    if (!$parent) {
                        $fail('没有找到父节点');
                        return;
                    }
                    $node = ProductType::query()->find($this->input('id'));
                    if ($node->parentid == $parentid) {
                        $fail('父节点没有改变,无法移动!');
                        return;
                    }
                    $allNode = $node->getAllChild()->pluck('id')->toArray();
                    if (in_array($parentid, $allNode)) {
                        $fail('不能移动到子节点下');
                    }
                }
            ],
        ];
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            'move' => $this->getMoveMessages(),
            default => []
        };
    }

    private function getCreateMessages(): array
    {
        return [
            'parentid.required' => '缺少[parentid]参数!',
            'parentid.exists'   => '没有找到父节点!',
            'name.required'     => '分类名称不能为空!'
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'   => '缺少[id]参数!',
            'id.exists'     => '没有找到需要修改的数据!',
            'name.required' => '分类名称不能为空!'
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少[id]参数!',
            'id.exists'   => '没有找到需要删除的数据!'
        ];
    }

    private function getMoveMessages(): array
    {
        return [
            'id.required'       => '缺少[id]参数!',
            'id.exists'         => '没有找到需要移动的数据!',
            'id.not_in'         => '不能移动根节点',
            'parentid.required' => '缺少[parentid]参数!'
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        $data = [
            'name' => $this->input('name')
        ];
        if (request()->route()->getActionMethod() == 'create') {
            $data['parentid'] = $this->input('parentid');
        }
        return $data;
    }
}
