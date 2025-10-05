<?php

namespace App\Http\Controllers\Web;

use App\Models\CustomerEconomic;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CustomerEconomicRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class CustomerEconomicController extends Controller
{
    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = CustomerEconomic::query()
            ->when($name = $request->input('name'), fn(Builder $query) => $query->whereLike('name', "%{$name}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    public function create(CustomerEconomicRequest $request): JsonResponse
    {
        $data = CustomerEconomic::query()->create(
            $request->formData()
        );
        return response_success($data);
    }

    public function info(CustomerEconomicRequest $request): JsonResponse
    {
        $data = CustomerEconomic::query()->find(
            $request->input('id')
        );
        return response_success($data);
    }

    public function update(CustomerEconomicRequest $request): JsonResponse
    {
        $data = CustomerEconomic::query()->find(
            $request->input('id')
        );
        $data->update(
            $request->formData()
        );
        return response_success($data);
    }

    public function remove(CustomerEconomicRequest $request): JsonResponse
    {
        CustomerEconomic::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
