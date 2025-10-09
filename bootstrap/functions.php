<?php

use App\Models\User;
use App\Models\Store;
use App\Traits\HasTree;
use App\Models\GoodsType;
use App\Models\Parameter;
use App\Models\Admin\AdminParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Auth\Authenticatable;

/*
|--------------------------------------------------------------------------
| 函数包
|--------------------------------------------------------------------------
|
*/

/**
 * 隐藏电话号码
 * @param string $phone 电话,多个用逗号分隔
 * @return string
 */
function hide_phone(string $phone): string
{
    $phone = explode(',', $phone);
    $data  = [];

    foreach ($phone as $p) {
        $len      = intval(strlen($p) / 2);
        $asterisk = str_repeat('*', $len);
        $data[]   = substr_replace($p, $asterisk, ceil($len / 2), $len);
    }

    return implode(',', $data);
}

/**
 * 把返回的数据集转换成Tree
 * @param array $list
 * @param string $pk
 * @param string $pid
 * @param string $child
 * @param int $root
 * @return array
 */
function list_to_tree(array $list, string $pk = 'id', string $pid = 'parentid', string $child = 'children', int $root = 0): array
{
    // 创建Tree
    $tree = array();
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[] =& $list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent           =& $refer[$parentId];
                    $parent[$child][] =& $list[$key];
                }
            }
        }
    }
    return $tree;
}

/**
 * 获取参数值
 * @param string|null $name
 * @param mixed $default
 * @return mixed
 */
function parameter(?string $name = null, ?string $default = null): mixed
{
    $parameters = Parameter::query()
        ->get()
        ->mapWithKeys(function (Parameter $item) {
            return [$item->name => $item->value];
        })
        ->toArray();
    return $name ? ($parameters[$name] ?? $default) : $parameters;
}

/**
 * 更新系统参数配置
 * @param string $name
 * @param string $value
 * @return void
 */
function setParameter(string $name, string $value): void
{
    $param = Parameter::query()->find($name);
    $param?->update(['value' => $value]);
}

/**
 * 获取管理后台参数配置
 * @param string|null $name 参数名称，为null时返回所有参数
 * @return mixed
 */
function admin_parameter(?string $name = null): mixed
{
    // 使用全局缓存
    $parameters = global_cache()->remember('admin_parameters', 3600, function () {
        return AdminParameter::query()
            ->select(['name', 'value', 'type'])
            ->get()
            ->mapWithKeys(function (AdminParameter $item) {
                return [$item->name => $item->value];
            })
            ->toArray();
    });

    // 如果没有传入参数名，返回所有参数
    if (empty($name)) {
        return $parameters;
    }

    // 返回指定参数的值
    return $parameters[$name] ?? null;
}

/**
 * 返回用户信息(为空返回当前登录用户)
 * @param int|null $id
 * @return User|null
 */
function user(?int $id = null): ?User
{
    if ($id !== null) {
        return User::query()->find($id);
    }
    return auth()->user();
}

/**
 * 获取当前登录管理员
 * @return User|Authenticatable|null
 */
function admin(): User|Authenticatable|null
{
    return auth('admin')->user();
}

/**
 * 生成搜索关键词
 * @param string $string
 * @return array
 */
function parse_pinyin(string $string): array
{
    return [
        pinyin_permalink($string, ''),
        pinyin_abbr($string),
        $string
    ];
}

/**
 * 获取商品类别名称
 * @param int|null $id
 * @return string|null
 */
function get_goods_type_name(?int $id): ?string
{
    if (empty($id)) {
        return null;
    }
    $r = GoodsType::getInfo($id);
    return $r ? $r->name : null;
}

/**
 * 获取项目类别名称
 * @param int|null $id
 * @return string|null
 */
function get_product_type_name(?int $id): ?string
{
    if (empty($id)) {
        return null;
    }
    $r = App\Models\ProductType::getInfo($id);
    return $r ? $r->name : null;
}

/**
 * 获取计量单位名称
 * @param int|null $id
 * @return string|null
 */
function get_unit_name(?int $id): ?string
{
    if (empty($id)) {
        return null;
    }
    $r = App\Models\Unit::getInfo($id);
    return $r ? $r->name : null;
}

/**
 * 获取仓库名称
 * @param int|null $id
 * @return string|null
 */
function get_warehouse_name(?int $id): ?string
{
    if (empty($id)) {
        return null;
    }
    $r = App\Models\Warehouse::getInfo($id);
    return $r ? $r->name : null;
}

/**
 * 获取仓库名称
 * @param int|null $id
 * @return string|null
 */
function get_supplier_name(?int $id): ?string
{
    if (empty($id)) {
        return null;
    }
    $r = App\Models\Supplier::getInfo($id);
    return $r ? $r->name : null;
}

/**
 * 获取科室名称
 * @param int|null $id
 * @return string|null
 */
function get_department_name(?int $id): ?string
{
    if (empty($id)) {
        return null;
    }
    $r = App\Models\Department::getInfo($id);
    return $r ? $r->name : null;
}

/**
 * 获取用户名
 */
function get_user_name($id)
{
    if (empty($id)) {
        return null;
    }
    $user = App\Models\User::getInfo($id);
    return $user ? $user->name : null;
}

/**
 * 获取未成交原因
 */
function get_failure_name($id)
{
    if (empty($id)) {
        return null;
    }
    $r = App\Models\Failure::getInfo($id);
    return $r ? $r->name : null;
}

/**
 * 获取媒介来源
 * @param int $id
 * @param bool $fullpath
 * @param string $glue
 * @return null|string
 */
function get_medium_name(int $id, bool $fullpath = false, string $glue = ' > '): ?string
{
    if (empty($id)) {
        return null;
    }
    $medium = App\Models\Medium::getInfo($id);
    if (!$medium) {
        return null;
    }
    return $fullpath ? $medium->getFullPath($glue) : $medium->name;
}

/**
 * 获取地址名称
 * @param int $id
 * @param bool $fullpath
 * @param string $glue
 * @return string|null
 */
function get_address_name(int $id, bool $fullpath = false, string $glue = ' > '): ?string
{
    if (empty($id)) {
        return null;
    }
    $address = App\Models\Address::getInfo($id);
    if (!$address) {
        return null;
    }
    return $fullpath ? $address->getFullPath($glue) : $address->name;
}

/**
 * 获取树形结构模型名称
 * @param string $model 模型类名
 * @param int $id
 * @param bool $fullpath 是否显示完整路径
 * @param string $glue 分隔符
 * @return string|null
 */
function get_tree_name(string $model, int $id, bool $fullpath = false, string $glue = ' > '): ?string
{
    if (empty($id) || !class_exists($model) || !in_array(HasTree::class, class_uses_recursive($model))) {
        return null;
    }

    $model = $model::getInfo($id);

    if (!$model) {
        return null;
    }

    return $fullpath ? $model->getFullPath($glue) : $model->name;
}

/**
 * 接诊类型
 * @param int|null $id
 * @return string|null
 */
function get_reception_type_name(?int $id): ?string
{
    if (empty($id)) {
        return null;
    }
    $type = App\Models\ReceptionType::getInfo($id);
    return $type ? $type->name : null;
}

/**
 * 获取费用类别名称
 * @param int|null $id
 * @return string|null
 */
function get_expense_category_name(?int $id): ?string
{
    if (empty($id)) {
        return null;
    }
    $r = App\Models\ExpenseCategory::getInfo($id);
    return $r ? $r->name : null;
}

/**
 * 获取收款账户名称
 * @param int|null $id
 * @return string|null
 */
function get_accounts_name(?int $id): ?string
{
    if (empty($id)) {
        return null;
    }
    $r = App\Models\Accounts::getInfo($id);
    return $r ? $r->name : null;
}

/**
 * 获取预约项目名称
 * @param int|null $id
 * @return string|null
 */
function get_item_name(?int $id): ?string
{
    if (empty($id)) {
        return null;
    }
    $r = App\Models\Item::getInfo($id);
    return $r ? $r->name : null;
}

/**
 * 获取项目名称(数组)
 * @param array $ids
 * @param string $glue
 * @return string|null
 */
function get_items_name(array $ids, string $glue = ' 、 '): ?string
{
    if (empty($ids)) {
        return null;
    }
    $names = [];
    foreach ($ids as $id) {
        $names[] = get_item_name($id);
    }
    return implode($glue, $names);
}

/**
 * 解析{销售人员|配台人员}占比
 * @param array $value
 * @return string|null
 */
function formatter_salesman(array $value = []): ?string
{
    if (empty($value)) {
        return null;
    }
    $text = [];
    foreach ($value as $k) {
        $text[] = get_user_name($k['user_id']);
    }
    return implode(',', $text);
}

/**
 * 获取附件url
 * @param string $url
 * @return string
 */
function get_attachment_url(string $url): string
{
    if (empty($url)) {
        return '';
    }

    $disk   = config('filesystems.default');
    $config = config('filesystems.disks.' . $disk);

    if ($config['driver'] == 'local') {
        return tenant_asset($url);
    }

    // 私有访问 生成url签名
    if (!empty($config['signed_url'])) {
        return Storage::disk($disk)->temporaryUrl($url, now()->addMinutes(10));
    }

    return Storage::disk($disk)->url($url);
}

if (!function_exists('store')) {
    /**
     * 返回门店信息
     * @param $id
     * @return Store
     */
    function store($id = null): Store
    {
        // 未指定门店id,返回当前登录门店
        // 暂时固定门店id为1
        if (is_null($id)) {
            $id = 1;
        }
        return Store::query()->find($id);
    }
}

/**
 * 请求成功
 * @param mixed $data
 * @param string $msg
 * @param int $code
 * @return JsonResponse
 */
function response_success(mixed $data = [], string $msg = '操作成功', int $code = 200): JsonResponse
{
    $json = [
        'msg'  => $msg,
        'data' => $data,
        'code' => $code
    ];
    return response()->json($json);
}

/**
 * 请求失败
 * @param mixed $data
 * @param string $msg
 * @param int $code
 * @return JsonResponse
 */
function response_error(mixed $data = [], string $msg = '操作失败', int $code = 400): JsonResponse
{
    $json = [
        'msg'  => $msg,
        'data' => $data,
        'code' => $code
    ];
    return response()->json($json);
}
