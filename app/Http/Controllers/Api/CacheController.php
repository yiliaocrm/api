<?php

namespace App\Http\Controllers\Api;

use App\Models\Item;
use App\Models\User;
use App\Models\Room;
use App\Models\Medium;
use App\Models\Address;
use App\Models\GoodsType;
use App\Models\Department;
use App\Models\ProductType;
use App\Models\FollowupTool;
use App\Models\FollowupType;
use App\Models\ReservationType;
use App\Models\ProductPackageType;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;

class CacheController extends Controller
{

    public function departments(Request $request): JsonResponse
    {
        $departments = Department::query()
            ->when($request->get('primary'), fn($query) => $query->where('primary', $request->get('primary')))
            ->when($request->get('disabled'), fn($query) => $query->where('disabled', $request->get('disabled')))
            ->get();
        return response_success(
            $departments
        );
    }

    public function goodsTypes(): JsonResponse
    {
        return response_success(
            GoodsType::all()
        );
    }

    public function productTypes(): JsonResponse
    {
        return response_success(
            ProductType::all()
        );
    }

    public function productPackageTypes(): JsonResponse
    {
        return response_success(
            ProductPackageType::all()
        );
    }

    public function followupTypes(): JsonResponse
    {
        return response_success(
            FollowupType::all()
        );
    }

    public function followupTools(): JsonResponse
    {
        return response_success(
            FollowupTool::all()
        );
    }

    public function mediums(): JsonResponse
    {
        return response_success(
            Medium::all()
        );
    }

    public function address(): JsonResponse
    {
        return response_success(
            Address::all()
        );
    }

    public function reservationTypes(): JsonResponse
    {
        return response_success(
            ReservationType::all()
        );
    }

    public function rooms(Request $request): JsonResponse
    {
        $department_id = $request->input('department_id');
        $rooms         = Room::query()
            ->when($department_id, fn($query) => $query->where('department_id', $department_id))
            ->get();
        return response_success($rooms);
    }

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
            ->when($request->input('role'), fn(Builder $query) => $query->leftJoin('role_users', 'role_users.user_id', '=', 'users.id')->leftJoin('roles', 'roles.id', '=', 'role_users.role_id')->where('roles.slug', $request->input('role')))
            // 禁止登录状态
            ->when($request->has('banned'), fn(Builder $query) => $query->where('banned', $request->input('banned')))
            ->get();
        return response_success($users);
    }

    public function items(Request $request): JsonResponse
    {
        $request->validate(
            [
                'parentid' => 'nullable|integer',
                'ids'      => 'nullable|array'
            ],
            [
                'parentid.integer' => '父级ID必须为整数',
                'ids.array'        => 'IDS必须为数组'
            ]
        );

        $items = Item::query()
            ->when($request->has('parentid'), fn(Builder $query) => $query->where('parentid', $request->input('parentid')))
            ->when($request->input('ids'), fn(Builder $query) => $query->whereIn('id', $request->input('ids')))
            ->when($request->has('keyword'), fn(Builder $query) => $query->where('keyword', 'like', '%' . $request->input('keyword') . '%'))
            ->get();

        return response_success($items);
    }
}
