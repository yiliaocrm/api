<?php

namespace App\Http\Requests\Consultant;

use App\Models\Goods;
use App\Models\Product;
use App\Models\Customer;
use App\Models\ProductType;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
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
            'customer_id'        => [
                'required',
                'exists:customer,id',
                function ($attribute, $customer_id, $fail) {
                    if (!parameter('consultant_allow_reception')) {
                        $fail('顾客必须经过[前台分诊]才能接待');
                    }
                    if (parameter('consultant_only_self_create')) {
                        $customer = Customer::query()->find($customer_id);
                        if ($customer->consultant != 0 && $customer->consultant != user()->id) {
                            $fail('系统开启{首诊制}现场咨询不匹配无法分诊');
                        }
                    }
                }
            ],
            'form'               => 'required|array',
            'form.department_id' => 'required|exists:department,id',
            'form.doctor'        => 'nullable|exists:users,id',
            'form.type'          => 'required',
            'form.ek_user'       => 'nullable|exists:users,id',
            'form.medium_id'     => 'required|numeric|min:2|exists:medium,id',
            'form.failure_id'    => 'nullable|numeric|min:2|exists:failure,id',
            'form.items'         => [
                'required',
                'array',
                'exists:item,id',
                function ($attribute, $items, $fail) {
                    if (!parameter('consultant_allow_multiple_item') && count($items) > 1) {
                        $fail('系统设置,不允许录入多个咨询项目!');
                    }
                }
            ],
            'form.remark'        => 'required',
            'order.*.type'       => 'required|in:goods,product',
            'order.*.goods_id'   => [
                'nullable',
                'integer',
                'exists:goods,id',
                function ($attribute, $goods_id, $fail) {
                    // 现场咨询零售物品,验证商品库存
                    $goods = Goods::query()->find($goods_id);
                    $basic = $goods->units->where('basic', 1)->first();

                    // 开单信息
                    $use_times   = $this->input(str_replace('goods_id', 'times', $attribute));    // 使用数量
                    $use_unit_id = $this->input(str_replace('goods_id', 'unit_id', $attribute));  // 使用单位
                    $current     = $goods->units->where('unit_id', $use_unit_id)->first();   // 当前商品单位信息

                    // 商品单位 跟基本单位 不一致
                    if ($basic->unit_id != $use_unit_id) {
                        $use_times = bcmul($use_times, $current->rate, 4); // 换算单位(高精度乘法)
                    }

                    if ($goods->inventory_number < $use_times) {
                        $fail("《{$goods->name}》库存信息不足!");
                    }
                }
            ],
            'order.*.product_id' => [
                'nullable',
                'integer',
                function ($attribute, $product_id, $fail) {
                    $product = Product::query()->find($product_id);
                    $items   = $this->input('form.items');

                    if (!$product) {
                        $fail('项目不存在!');
                    }

                    // // 验证[咨询项目]必须与[开单项目分类](一致)
                    // if (parameter('consultant_enable_item_product_type_sync') && parameter('consultant_enable_item_product_type_sync') == 1) {
                    //     if (!in_array($product->type_id, $items)) {
                    //         $fail("《{$product->name}》[项目分类]与[咨询项目]不一致");
                    //     }
                    // }

                    // // 验证[咨询项目]必须与[开单项目分类](包含)
                    // if (parameter('consultant_enable_item_product_type_sync') && parameter('consultant_enable_item_product_type_sync') == 2) {
                    //     $types    = ProductType::query()->whereIn('id', $items)->get();
                    //     $whereRaw = [];

                    //     foreach ($types as $type) {
                    //         $whereRaw[] = "tree like '%{$type->tree}-%' or id = {$type->id}";
                    //     }

                    //     // 所有包含的节点
                    //     $nodes = ProductType::query()
                    //         ->select('id')
                    //         ->whereRaw(implode(' or ', $whereRaw))
                    //         ->orderBy('id', 'asc')
                    //         ->get()
                    //         ->pluck('id')
                    //         ->toArray();

                    //     if (!in_array($product->type_id, $nodes)) {
                    //         $fail("《{$product->name}》不在[咨询项目]节点中");
                    //     }
                    // }
                }
            ],
            'order.*.package_id' => 'nullable|integer|exists:product_package,id',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required'        => '[customer_id]不能为空!',
            'customer_id.exists'          => '[顾客信息]不存在!',
            'form.department_id.required' => '[咨询科室]不能为空!',
            'form.doctor.exists'          => '[接诊医生]不存在!',
            'form.type'                   => '[接诊类型]不能为空!',
            'form.ek_user.exists'         => '[二开人员]不存在!',
            'form.medium_id.required'     => '[媒介来源]不能为空!',
            'form.medium_id.min'          => '[媒介来源]错误!',
            'form.failure_id.exists'      => '[未成交原因]不存在!',
            'form.items.required'         => '[咨询项目]不能为空!',
            'form.items.exists'           => '[咨询项目]错误!',
            'form.remark.required'        => '[咨询情况]不能为空!',
            'order.*.product_id.integer'  => '[项目名称]必须为数字!',
            'order.*.package_id.exists'   => '[套餐名称]不存在!',
        ];
    }

    public function formData(): array
    {
        $data = [
            'customer_id'   => $this->input('customer_id'),
            'department_id' => $this->input('form.department_id'),
            'items'         => $this->input('form.items'),
            'type'          => $this->input('form.type'),
            'status'        => 1, // 未成交
            'consultant'    => user()->id,
            'reception'     => user()->id,
            'user_id'       => user()->id,
            'medium_id'     => $this->input('form.medium_id'),
            'ek_user'       => $this->input('form.ek_user'),
            'doctor'        => $this->input('form.doctor'),
            'receptioned'   => 1,
            'failure_id'    => $this->input('form.failure_id'),
            'remark'        => $this->input('form.remark')
        ];

        /**
         * 开了项目单(根据项目归属分类,设置items)
         * 1、开启咨询项目多选
         * 2、允许自动填充
         */
        if ($this->input('order') && parameter('consultant_allow_multiple_item')) {
            $ids           = collect($this->input('order'))->whereNotNull('product_id')->pluck('product_id')->toArray();
            $products      = Product::query()->whereIn('id', $ids)->get();
            $data['items'] = collect($this->input('form.items'))->merge($products->pluck('type_id')->values())->unique()->values()->toArray();
        }

        return $data;
    }

    public function orderData($customer_id): array
    {
        $data   = [];
        $orders = $this->input('order');

        foreach ($orders as $order) {
            $data[] = [
                'customer_id'   => $customer_id,
                'status'        => 2, // 待收费
                'type'          => $order['type'],
                'package_id'    => $order['package_id'] ?? null,
                'package_name'  => $order['package_name'] ?? null,
                'product_id'    => $order['product_id'] ?? null,
                'product_name'  => $order['product_name'] ?? null,
                'splitable'     => $order['splitable'] ?? null,
                'editable'      => $order['editable'] ?? null,
                'goods_id'      => $order['goods_id'] ?? null,
                'goods_name'    => $order['goods_name'] ?? null,
                'times'         => $order['times'],
                'unit_id'       => $order['unit_id'] ?? null,
                'specs'         => $order['specs'] ?? null,
                'price'         => $order['price'],
                'sales_price'   => $order['sales_price'],
                'payable'       => $order['payable'],
                'amount'        => 0,
                'coupon'        => 0,
                'department_id' => $order['department_id'],
                'salesman'      => $order['salesman'],
                'remark'        => $order['remark'] ?? null,
                'user_id'       => user()->id,
            ];
        }

        return $data;
    }
}
