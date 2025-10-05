<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExecutionController extends Controller
{

    /**
     * 获取配台角色
     * @param Request $request
     * @return JsonResponse
     */
    public function participants(Request $request): JsonResponse
    {
        $roles = Role::query()
            ->where('execution', 1)
            ->get();

        $users = User::query()
            ->with('roles')
            ->when($request->input('department_id'), function (Builder $query) use ($request) {
                $query->where('users.department_id', $request->input('department_id'));
            })
            ->whereHas('roles', function (Builder $query) use ($roles) {
                $query->whereIn('role_users.role_id', $roles->pluck('id')->toArray());
            })
            ->where('banned', 0)
            ->get();

        $departments = Department::query()
            ->where('disabled', 0)
            ->where('primary', 1)
            ->get()
            ->toArray();

        return response_success([
            'roles'       => $roles,
            'users'       => $users,
            'departments' => $departments
        ]);
    }


}
