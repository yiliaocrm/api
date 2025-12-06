<?php

namespace App\Http\Controllers\Web;

use App\Helpers\AttachmentHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DrugRequest;
use App\Models\Goods;
use App\Models\GoodsType;
use App\Models\InventoryBatchs;
use App\Models\InventoryDetail;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DrugController extends Controller
{
    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = Goods::query()
            ->with([
                'type',
                'units' => fn($query) => $query->orderByDesc('basic')->orderByDesc('id'),
                'alarms'
            ])
            ->when($request->input('type_id') && $request->input('type_id') != 1, function (Builder $query) use ($request) {
                $query->whereIn('type_id', GoodsType::query()->find($request->input('type_id'))->getAllChild()->pluck('id'));
            })
            ->when($request->input('keyword'), function ($query) use ($request) {
                $query->where('keyword', 'like', '%' . $request->input('keyword') . '%');
            })
            ->when($request->input('warn_days_start') && $request->input('warn_days_end'), function (Builder $query) use ($request) {
                $query->whereBetween('warn_days', [
                    $request->input('warn_days_start'),
                    $request->input('warn_days_end')
                ]);
            })
            ->when($request->input('inventory_number_start') && $request->input('inventory_number_end'), function (Builder $query) use ($request) {
                $query->whereBetween('inventory_number', [
                    $request->input('inventory_number_start'),
                    $request->input('inventory_number_end')
                ]);
            })
            ->when($request->input('inventory_amount_start') && $request->input('inventory_amount_end'), function (Builder $query) use ($request) {
                $query->whereBetween('inventory_amount', [
                    $request->input('inventory_amount_start'),
                    $request->input('inventory_amount_end')
                ]);
            })
            ->orderBy($sort, $order)->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建药品
     * @param DrugRequest $request
     * @return JsonResponse
     */
    public function create(DrugRequest $request): JsonResponse
    {
        $drug = Goods::query()->create(
            $request->getGoodsData()
        );

        $drug->unit()->sync(
            $request->getGoodsUnit()
        );

        $drug->alarm()->sync(
            $request->getGoodsAlarm()
        );

        $drug->load(['units', 'alarms']);

        return response_success($drug);
    }

    /**
     * 更新药品
     * @param DrugRequest $request
     * @return JsonResponse
     */
    public function update(DrugRequest $request): JsonResponse
    {
        $drug = Goods::query()->find(
            $request->input('id')
        );

        // 同步信息
        $drug->unit()->sync($request->getGoodsUnit());
        $drug->alarm()->sync($request->getGoodsAlarm());

        $drug->update(
            $request->getGoodsData()
        );

        $drug->load(['units', 'alarms']);

        return response_success($drug);
    }

    /**
     * 上传图片
     * @param Request $request
     * @param AttachmentHelper $attachment
     * @return JsonResponse
     */
    public function upload(Request $request, AttachmentHelper $attachment): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg',
        ], [
            'file.required' => '请选择上传文件',
            'file.file'     => '上传文件必须是图片',
            'file.mimes'    => '上传文件类型不符合要求',
        ]);
        $file = $attachment->upload($request->file('file'), 'goods');
        $path = get_attachment_url($file['file_path']);
        return response_success([
            'path' => $path
        ]);
    }

    /**
     * 批量启用
     * @param DrugRequest $request
     * @return JsonResponse
     */
    public function enable(DrugRequest $request): JsonResponse
    {
        Goods::query()
            ->whereIn('id', $request->input('ids'))
            ->update(['disabled' => 0]);
        return response_success();
    }

    /**
     * 批量禁用
     * @param DrugRequest $request
     * @return JsonResponse
     */
    public function disable(DrugRequest $request): JsonResponse
    {
        Goods::query()
            ->whereIn('id', $request->input('ids'))
            ->update([
                'disabled' => 1
            ]);
        return response_success();
    }

    /**
     * 删除药品
     * @param DrugRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function remove(DrugRequest $request): JsonResponse
    {
        Goods::query()->whereIn('id', explode(',', $request->input('ids')))->delete();
        return response_success();
    }

    /**
     * 药品库存变动明细
     * @param DrugRequest $request
     * @return JsonResponse
     */
    public function inventoryDetail(DrugRequest $request): JsonResponse
    {
        $sort  = $request->input('sort', 'id');
        $rows  = $request->input('rows', 10);
        $order = $request->input('order', 'desc');
        $query = InventoryDetail::query()
            ->with([
                'warehouse:id,name',
            ])
            ->where('goods_id', $request->input('goods_id'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 物品批次管理
     * @param DrugRequest $request
     * @return JsonResponse
     */
    public function inventoryBatch(DrugRequest $request): JsonResponse
    {
        $sort  = $request->input('sort', 'id');
        $rows  = $request->input('rows', 10);
        $order = $request->input('order', 'desc');
        $query = InventoryBatchs::query()
            ->where('goods_id', $request->input('goods_id'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }
}
