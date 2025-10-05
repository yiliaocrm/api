<?php

namespace App\Http\Controllers\Web;

use App\Models\Room;
use App\Models\Tags;
use App\Models\Unit;
use App\Models\User;
use App\Models\Menu;
use App\Models\Item;
use App\Models\Store;
use App\Models\Field;
use App\Models\Medium;
use App\Models\Address;
use App\Models\Failure;
use App\Models\Product;
use App\Models\WebMenu;
use App\Models\Accounts;
use App\Models\Qufriend;
use App\Models\Supplier;
use App\Models\GoodsType;
use App\Models\Department;
use App\Models\ProductType;
use App\Models\CustomerJob;
use App\Models\FollowupTool;
use App\Models\FollowupType;
use App\Models\FollowupRole;
use App\Models\PurchaseType;
use App\Models\CustomerGroup;
use App\Models\ReceptionType;
use App\Models\ProductPackage;
use App\Models\ReservationType;
use App\Models\ExpenseCategory;
use App\Models\CustomerEconomic;
use App\Models\ProductPackageType;
use App\Models\FollowupTemplateType;
use App\Models\CustomerGroupCategory;
use App\Models\DepartmentPickingType;
use App\Models\CustomerPhoneRelationship;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CacheRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

class CacheController extends Controller
{
    /**
     * 所有门店
     * @return JsonResponse
     */
    public function stores(): JsonResponse
    {
        return response_success(
            Store::query()->get()
        );
    }

    /**
     * 收费账户
     * @return JsonResponse
     */
    public function accounts(): JsonResponse
    {
        return response_success(
            Accounts::query()->get()
        );
    }

    /**
     * 获取所有员工
     * @param Request $request
     * @return JsonResponse
     */
    public function users(Request $request): JsonResponse
    {
        $users = User::query()
            ->select([
                'users.id',
                'users.name',
                'users.department_id',
                'users.keyword'
            ])
            // 根据角色查询用户
            ->when($request->input('role'), function (Builder $builder) use ($request) {
                $builder->leftJoin('role_users', 'role_users.user_id', '=', 'users.id')
                    ->leftJoin('roles', 'roles.id', '=', 'role_users.role_id')
                    ->where('roles.slug', $request->input('role'));
            })
            // 禁止登录状态
            ->when($request->has('banned'), function (Builder $builder) use ($request) {
                $builder->where('users.banned', $request->input('banned'));
            })
            // 预约设置
            ->when($request->has('appointment') && $request->has('role'), function (Builder $query) {
                $query->leftJoin('appointment_configs', function (JoinClause $join) {
                    $join->on('appointment_configs.target_id', '=', 'users.id')
                        ->where('appointment_configs.config_type', request('role'));
                })
                    ->where(fn(Builder $query) => $query->where('appointment_configs.display', 1)->orWhereNull('appointment_configs.display'));
            })
            ->get();

        return response_success($users);
    }

    /**
     * 角色组
     * @return JsonResponse
     */
    public function roles(): JsonResponse
    {
        return response_success(
            DB::table('roles')
                ->select(['id', 'name', 'slug'])
                ->get()
        );
    }

    /**
     * 科室
     * @param Request $request
     * @return JsonResponse
     */
    public function departments(Request $request): JsonResponse
    {
        $data = Department::query()
            ->select([
                'department.id',
                'department.name',
                'department.primary',
                'department.keyword',
                'department.disabled'
            ])
            ->when($request->has('primary'), fn(Builder $query) => $query->where('department.primary', $request->input('primary')))
            ->when($request->has('disabled'), fn(Builder $query) => $query->where('department.disabled', $request->input('disabled')))
            // 预约设置
            ->when($request->has('appointment'), function (Builder $query) {
                $query->leftJoin('appointment_configs', function (JoinClause $join) {
                    $join->on('appointment_configs.target_id', '=', 'department.id')
                        ->where('appointment_configs.config_type', 'department');
                })
                    ->where(fn(Builder $query) => $query->where('appointment_configs.display', 1)->orWhereNull('appointment_configs.display'));
            })
            ->get();
        return response_success($data);
    }

    /**
     * 诊室
     * @param Request $request
     * @return JsonResponse
     */
    public function rooms(Request $request): JsonResponse
    {
        $rooms = Room::query()
            ->select([
                'room.id',
                'room.name',
            ])
            // 预约设置
            ->when($request->has('appointment'), function (Builder $query) {
                $query->leftJoin('appointment_configs', function (JoinClause $join) {
                    $join->on('appointment_configs.target_id', '=', 'room.id')
                        ->where('appointment_configs.config_type', 'room');
                })
                    ->where(fn(Builder $query) => $query->where('appointment_configs.display', 1)->orWhereNull('appointment_configs.display'));
            })
            ->get();
        return response_success($rooms);
    }

    /**
     * 回访工具
     * @return JsonResponse
     */
    public function followupTool(): JsonResponse
    {
        return response_success(
            FollowupTool::query()->get()
        );
    }

    /**
     * 回访类别
     * @return JsonResponse
     */
    public function followupType(): JsonResponse
    {
        return response_success(
            FollowupType::query()->get()
        );
    }

    /**
     * 回访角色
     * @return JsonResponse
     */
    public function followupRole(): JsonResponse
    {
        return response_success(
            FollowupRole::query()->get()
        );
    }

    /**
     * 职业信息
     * @return JsonResponse
     */
    public function customerJob(): JsonResponse
    {
        return response_success(
            CustomerJob::query()->get()
        );
    }

    /**
     * 顾客分组
     * @param CacheRequest $request
     * @return JsonResponse
     */
    public function customerGroup(CacheRequest $request): JsonResponse
    {
        $type     = $request->input('type');
        $cascader = $request->boolean('cascader');
        $groups   = CustomerGroup::query()
            ->select([
                'id',
                'name',
                'type'
            ])
            ->when($type, fn(Builder $query) => $query->where('type', $type))
            ->get();

        if (!$cascader) {
            return response_success($groups);
        }

        // 返回级联数据
        $result = CustomerGroupCategory::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(function ($category) use ($groups) {
                $children = $groups->map(fn($group) => [
                    'value' => $group->id,
                    'label' => $group->name,
                ]);
                return [
                    'value'    => $category->id,
                    'label'    => $category->name,
                    'children' => $children,
                ];
            });
        return response_success($result);
    }

    /**
     * 经济能力
     * @return JsonResponse
     */
    public function customerEconomic(): JsonResponse
    {
        return response_success(
            CustomerEconomic::query()->get()
        );
    }

    /**
     * 标签
     * @return JsonResponse
     */
    public function tags(): JsonResponse
    {
        $tags = Tags::query()
            ->select(['id', 'name as text', 'name', 'parentid', 'child', 'keyword'])
            ->get()
            ->toArray();
        return response_success(
            request()->has('cascader') ? list_to_tree($tags) : $tags
        );
    }

    /**
     * 咨询项目
     * @return JsonResponse
     */
    public function items(): JsonResponse
    {
        $items = Item::query()
            ->select([
                'id',
                'name',
                'name as text',
                'parentid',
                'child',
                'keyword'
            ])
            ->get()
            ->toArray();
        return response_success(
            request()->has('cascader') ? list_to_tree($items) : $items
        );
    }

    /**
     * 媒介来源
     * @return JsonResponse
     */
    public function mediums(): JsonResponse
    {
        $mediums = Medium::query()
            ->select(['id', 'name as text', 'name', 'parentid', 'child'])
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->toArray();
        return response_success(
            request()->has('cascader') ? list_to_tree($mediums) : $mediums
        );
    }

    /**
     * 供应商
     * @return JsonResponse
     */
    public function suppliers(): JsonResponse
    {
        $data = Supplier::query()
            ->select(['id', 'name', 'keyword'])
            ->get();
        return response_success($data);
    }

    /**
     * 回访模板分类
     * @return JsonResponse
     */
    public function followupTemplateType(): JsonResponse
    {
        return response_success(
            FollowupTemplateType::query()->get()
        );
    }

    /**
     * 分诊类型
     * @return JsonResponse
     */
    public function receptionType(): JsonResponse
    {
        return response_success(
            ReceptionType::query()->get()
        );
    }

    /**
     * 地区数据
     * @return JsonResponse
     */
    public function address(): JsonResponse
    {
        $address = Address::query()
            ->select(['id', 'name as text', 'name', 'parentid', 'child'])
            ->get()
            ->toArray();
        return response_success(
            request()->has('cascader') ? list_to_tree($address) : $address
        );
    }

    /**
     * 未成交原因
     * @return JsonResponse
     */
    public function failures(): JsonResponse
    {
        $failure = Failure::query()
            ->get()
            ->toArray();
        return response_success(
            request()->has('cascader') ? list_to_tree($failure) : $failure
        );
    }

    /**
     * 仓库
     * @return JsonResponse
     */
    public function warehouse(): JsonResponse
    {
        return response_success(
            DB::table('warehouse')->get()
        );
    }

    /**
     * 商品分类
     * @return JsonResponse
     */
    public function goodsType(): JsonResponse
    {
        $types = GoodsType::query()->get()->toArray();
        return response_success(
            request()->has('cascader') ? list_to_tree($types) : $types
        );
    }

    /**
     * 费用类别
     * @return JsonResponse
     */
    public function expenseCategory(): JsonResponse
    {
        return response_success(
            ExpenseCategory::query()->select(['id', 'name', 'keyword'])->get()
        );
    }

    /**
     * 收费项目分类
     * @return JsonResponse
     */
    public function productType(): JsonResponse
    {
        $types = ProductType::query()
            ->get()
            ->toArray();
        return response_success(
            request()->has('cascader') ? list_to_tree($types) : $types
        );
    }

    /**
     * 收费项目套餐分类
     * @return JsonResponse
     */
    public function productPackageType(): JsonResponse
    {
        $types = ProductPackageType::query()->get()->toArray();
        return response_success(
            request()->has('cascader') ? list_to_tree($types) : $types
        );
    }

    /**
     * 网电报单受理类型
     * @return JsonResponse
     */
    public function reservationType(): JsonResponse
    {
        return response_success(
            ReservationType::all()
        );
    }

    /**
     * 菜单
     * @return JsonResponse
     */
    public function menu(): JsonResponse
    {
        $menus = Menu::query()
            ->orderBy('order')
            ->orderBy('id')
            ->get();
        return response_success($menus);
    }

    /**
     * 新版菜单
     * @param Request $request
     * @return JsonResponse
     */
    public function webmenu(Request $request): JsonResponse
    {
        $menus = WebMenu::query()
            ->get()
            ->toArray();

        return response_success(
            $request->has('cascader') ? list_to_tree($menus) : $menus
        );
    }

    /**
     * 计量单位
     * @return JsonResponse
     */
    public function unit(): JsonResponse
    {
        return response_success(
            Unit::query()->get()
        );
    }

    /**
     * 采购入库类别
     * @return JsonResponse
     */
    public function purchaseType(): JsonResponse
    {
        return response_success(
            PurchaseType::query()->get()
        );
    }

    /**
     * 科室领料类型
     * @return JsonResponse
     */
    public function departmentPickingType(): JsonResponse
    {
        return response_success(
            DepartmentPickingType::query()->get()
        );
    }

    /**
     * 亲友关系
     * @return JsonResponse
     */
    public function qufriend(): JsonResponse
    {
        return response_success(
            Qufriend::query()->get()
        );
    }

    /**
     * 电话关系
     * @return JsonResponse
     */
    public function phoneRelationship(): JsonResponse
    {
        return response_success(
            CustomerPhoneRelationship::query()->get()
        );
    }

    /**
     * 前端缓存依赖
     * @param Request $request
     * @return JsonResponse
     */
    public function dependency(Request $request): JsonResponse
    {
        $caches = [];
        $key    = explode(',', $request->input('key'));

        foreach ($key as $cache) {
            $method = 'get' . strtoupper($cache) . 'Cache';
            if (method_exists($this, $method)) {
                $caches[] = [
                    'key'  => $cache,
                    'data' => $this->$method()
                ];
            }
        }

        return response_success($caches);
    }

    # 菜单
    private function getMenuCache()
    {
        return Menu::query()->get();
    }

    # 预约项目
    private function getItemCache()
    {
        return Item::query()->select(['id', 'name as text', 'parentid', 'child', 'keyword'])->get();
    }

    # 科室数据
    private function getDepartmentCache()
    {
        return Department::query()->select(['id', 'name', 'primary', 'keyword', 'disabled'])->get();
    }

    // 费用类别
    private function getExpense_categoryCache()
    {
        return ExpenseCategory::query()->select(['id', 'name', 'keyword'])->get();
    }

    # 项目
    private function getProductCache()
    {
        return Product::all();
    }

    # 产品分类
    private function getProduct_typeCache()
    {
        return ProductType::all();
    }

    private function getProduct_package_typeCache()
    {
        return ProductPackageType::all();
    }

    # 开单项目套餐
    private function getProduct_packageCache()
    {
        return ProductPackage::all();
    }

    # datagrid 字段配置
    public function getFieldsCache()
    {
        return Field::query()
            ->where('user_id', user()->id)
            ->get();
    }

    # 数据库表配置文件
    private function getSettingCache()
    {
        return config('setting');
    }
}
