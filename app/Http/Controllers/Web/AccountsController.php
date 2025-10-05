<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\AccountRequest;
use App\Models\Accounts;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountsController extends Controller
{
    /**
     * 列表
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $rows = $request->input('rows', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = Accounts::query()
            ->when($request->input('name'), function (Builder $builder) use ($request) {
                $builder->where('name', 'like', '%' . $request->input('name') . '%');
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 新增
     * @param AccountRequest $request
     * @return JsonResponse
     */
    public function create(AccountRequest $request)
    {
        $data = Accounts::query()->create(
            $request->formData()
        );
        return response_success($data);
    }

    /**
     * 更新信息
     * @param AccountRequest $request
     * @return JsonResponse
     */
    public function update(AccountRequest $request)
    {
        $info = Accounts::query()->find(
            $request->input('id')
        );
        $info->update(
            $request->formData()
        );
        return response_success($info);
    }

    /**
     * 删除信息
     * @param AccountRequest $request
     * @return JsonResponse
     */
    public function remove(AccountRequest $request)
    {
        Accounts::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
