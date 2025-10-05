<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\FollowupRole\CreateRequest;
use App\Http\Requests\FollowupRole\RemoveRequest;
use App\Http\Requests\FollowupRole\UpdateRequest;
use App\Models\FollowupRole;
use Exception;
use Throwable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FollowupRoleController extends Controller
{
    public function manage(Request $request)
    {
        $rows  = $request->input('rows', 100);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = FollowupRole::query()
            ->when($request->input('name'), function (Builder $builder) use ($request) {
                $builder->where('name', 'like', '%' . $request->input('name') . '%');
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

    /**
     * 创建回访角色
     * @param CreateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(CreateRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            $role = FollowupRole::query()->create(
                $request->formData()
            );

            DB::commit();

            return response_success($role);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 更新回访角色
     * @param UpdateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function update(UpdateRequest $request)
    {
        DB::beginTransaction();
        try {

            $role = FollowupRole::query()->find(
                $request->input('id')
            );
            $role->update(
                $request->formData()
            );

            DB::commit();
            return response_success($role);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 删除角色
     * @param RemoveRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function remove(RemoveRequest $request)
    {
        DB::beginTransaction();
        try {

            FollowupRole::query()->find($request->input('id'))->delete();

            DB::commit();
            return response_success();

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }
}
