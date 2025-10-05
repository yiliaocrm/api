<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CustomerRfmRequest;
use App\Models\RfmRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerRfmController extends Controller
{
    /**
     * RFM管理
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $name  = $request->input('name');
        $query = RfmRule::query()
            ->when($name, fn(Builder $query) => $query->where('name', 'like', '%' . $name . '%'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 读取RFM参数
     * @return JsonResponse
     */
    public function getConfig(): JsonResponse
    {
        $recency   = parameter('rfm_recency');
        $frequency = parameter('rfm_frequency');
        $monetary  = parameter('rfm_monetary');
        return response_success([
            'recency'   => $recency,
            'frequency' => $frequency,
            'monetary'  => $monetary
        ]);
    }

    /**
     * 保存RFM配置
     * @param CustomerRfmRequest $request
     * @return JsonResponse
     */
    public function store(CustomerRfmRequest $request): JsonResponse
    {
        $recency   = json_encode($request->input('recency'));
        $frequency = json_encode($request->input('frequency'));
        $monetary  = json_encode($request->input('monetary'));

        setParameter('rfm_recency', $recency);
        setParameter('rfm_frequency', $frequency);
        setParameter('rfm_monetary', $monetary);

        return response_success([
            'recency'   => $recency,
            'frequency' => $frequency,
            'monetary'  => $monetary
        ]);
    }

    /**
     * 创建RFM规则
     * @param CustomerRfmRequest $request
     * @return JsonResponse
     */
    public function create(CustomerRfmRequest $request): JsonResponse
    {
        $rules = RfmRule::query()->create(
            $request->formData()
        );
        return response_success($rules);
    }
}
