<?php

namespace App\Http\Controllers\Web;

use App\Models\ExpenseCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ExpenseCategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class ExpenseCategoryController extends Controller
{
    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = ExpenseCategory::query()
            ->when($name = $request->input('name'), fn(Builder $query) => $query->whereLike('name', "%{$name}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 创建费用类别
     * @param ExpenseCategoryRequest $request
     * @return JsonResponse
     */
    public function create(ExpenseCategoryRequest $request): JsonResponse
    {
        $data = ExpenseCategory::query()->create(
            $request->formData()
        );
        return response_success($data);
    }

    public function update(ExpenseCategoryRequest $request): JsonResponse
    {
        $data = ExpenseCategory::query()->find(
            $request->input('id')
        );
        $data->update(
            $request->formData()
        );
        return response_success($data);
    }

    public function remove(ExpenseCategoryRequest $request): JsonResponse
    {
        ExpenseCategory::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
