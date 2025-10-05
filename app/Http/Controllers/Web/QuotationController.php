<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Goods;
use App\Models\GoodsType;
use App\Models\Product;
use App\Models\ProductPackage;
use App\Models\ProductPackageType;
use App\Models\ProductType;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    /**
     * 返回所有收费项目分类
     * @return \Illuminate\Http\JsonResponse
     */
    public function productType()
    {
        $type = ProductType::query()
            ->select('id', 'name AS text', 'parentid', 'child')
            ->get()
            ->toArray();
        $data = list_to_tree($type);
        return response_success($data);
    }

    /**
     * 开单项目列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function productList(Request $request)
    {
        $data    = [];
        $sort    = $request->input('sort', 'id');
        $rows    = $request->input('rows', 10);
        $order   = $request->input('order', 'desc');
        $type_id = $request->input('type_id');
        $query   = Product::query()
            ->with('type:id,name')
            ->whereIn('type_id', ProductType::query()->find($type_id)->getAllChild()->pluck('id'))
            ->when($request->input('keyword'), function ($query) use ($request) {
                $query->where('keyword', 'like', '%' . $request->input('keyword') . '%');
            })
            ->where('disabled', 0)
            ->orderBy($sort, $order)
            ->paginate($rows);

        if ($query) {
            $data['rows']  = $query->items();
            $data['total'] = $query->total();
        } else {
            $data['rows']  = [];
            $data['total'] = 0;
        }

        return response_success($data);
    }

    /**
     * 套餐分类
     * @return \Illuminate\Http\JsonResponse
     */
    public function productPackageType()
    {
        $type = ProductPackageType::query()
            ->select('id', 'name AS text', 'parentid', 'child')
            ->get()
            ->toArray();
        $data = list_to_tree($type);
        return response_success($data);
    }

    /**
     * 套餐列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function productPackageList(Request $request)
    {
        $data  = [];
        $rows  = request('rows', 10);
        $query = ProductPackage::query()
            ->with(['type:id,name', 'details'])
            ->select('*')
            // 关键词查询
            ->when($request->input('keyword'), function ($query) use ($request) {
                $query->where('keyword', 'like', '%' . $request->input('keyword') . '%');
            })
            // 分类
            ->when($request->input('type_id'), function ($query) use ($request) {
                $query->whereIn('type_id', ProductPackageType::query()->find($request->input('type_id'))->getAllChild()->pluck('id'));
            })
            ->orderBy('id', 'desc')
            ->paginate($rows);

        if ($query) {
            $data['rows']  = $query->items();
            $data['total'] = $query->total();
        } else {
            $data['rows']  = [];
            $data['total'] = 0;
        }

        return response_success($data);
    }

    /**
     * 物品分类
     * @return \Illuminate\Http\JsonResponse
     */
    public function goodsType()
    {
        $root = GoodsType::query()->find(1);
        $data = $root->select('id', 'name AS text', 'parentid', 'child', 'deleteable', 'editable')
            ->where(function ($query) use ($root) {
                $query->where('tree', 'like', "{$root->tree}-%")->orWhere('id', $root->id);
            })
            ->orderBy('id', 'ASC')
            ->get()
            ->toArray();

        return response_success(
            list_to_tree($data, 'id', 'parentid', 'children', $root->parentid)
        );
    }

    /**
     * 物品列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function goodsList(Request $request)
    {
        $rows  = request('rows', 10);
        $sort  = request('sort', 'id');
        $order = request('order', 'desc');
        $data  = [];

        $query = Goods::query()
            ->with(['type', 'basicUnit'])
            ->when(request('type_id') && request('type_id') != 1, function ($query) {
                $query->whereIn('type_id', GoodsType::query()->find(request('type_id'))->getAllChild()->pluck('id'));
            })
            ->when(request('keyword'), function ($query) {
                $query->where('keyword', 'like', '%' . request('keyword') . '%');
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        if ($query) {
            $data['rows']  = $query->items();
            $data['total'] = $query->total();
        } else {
            $data['rows']  = [];
            $data['total'] = 0;
        }

        return response_success($data);
    }
}
