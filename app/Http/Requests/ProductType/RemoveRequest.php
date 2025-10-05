<?php

namespace App\Http\Requests\ProductType;

use App\Models\Item;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ReceptionItems;
use App\Models\ReservationItems;
use Illuminate\Foundation\Http\FormRequest;

class RemoveRequest extends FormRequest
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
}
