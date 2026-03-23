<?php

namespace App\Services;

use App\Exceptions\HisException;
use App\Models\GoodsUnit;
use App\Models\InventoryBatchs;
use App\Models\InventoryCheck;
use App\Models\InventoryLoss;
use App\Models\InventoryOverflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class InventoryCheckService
{
    /**
     * 审核盘点单，按差异生成报损/报溢草稿
     *
     * @throws HisException|Throwable
     */
    public function approve(int $id): InventoryCheck
    {
        DB::beginTransaction();
        try {
            $check = InventoryCheck::query()
                ->with(['details'])
                ->lockForUpdate()
                ->find($id);

            if (! $check) {
                throw new HisException('没有找到盘点单据');
            }

            if ((int) $check->status !== 1) {
                throw new HisException('单据状态错误,无法重复审核!');
            }

            if ($check->inventory_loss_id || $check->inventory_overflow_id) {
                throw new HisException('盘点差异单据已生成,请勿重复审核!');
            }

            $negativeDetails = $check->details->filter(fn ($detail) => (float) $detail->diff_number < 0)->values();
            $positiveDetails = $check->details->filter(fn ($detail) => (float) $detail->diff_number > 0)->values();

            $inventoryLossId = null;
            $inventoryOverflowId = null;

            if ($negativeDetails->isNotEmpty()) {
                $loss = InventoryLoss::query()->create([
                    'key' => 'BSD'.date('Ymd').str_pad((InventoryLoss::query()->whereDate('created_at', now()->toDateString())->count() + 1), 4, '0', STR_PAD_LEFT),
                    'date' => $check->date,
                    'warehouse_id' => $check->warehouse_id,
                    'department_id' => $check->department_id,
                    'user_id' => $check->user_id,
                    'remark' => $check->remark,
                    'status' => 1,
                    'amount' => 0,
                    'create_user_id' => user()->id,
                ]);

                $lossDetailRows = [];
                foreach ($negativeDetails as $detail) {
                    $lossDetailRows[] = $this->buildLossDetailRow($check, $loss, $detail);
                }

                $loss->details()->createMany($lossDetailRows);
                $loss->update([
                    'amount' => collect($lossDetailRows)->sum('amount'),
                ]);

                $inventoryLossId = $loss->id;
            }

            if ($positiveDetails->isNotEmpty()) {
                $overflow = InventoryOverflow::query()->create([
                    'key' => 'BYD'.date('Ymd').str_pad((InventoryOverflow::query()->whereDate('created_at', now()->toDateString())->count() + 1), 4, '0', STR_PAD_LEFT),
                    'date' => $check->date,
                    'warehouse_id' => $check->warehouse_id,
                    'department_id' => $check->department_id,
                    'user_id' => $check->user_id,
                    'remark' => $check->remark,
                    'status' => 1,
                    'amount' => $positiveDetails->sum(function ($detail) {
                        return (float) $detail->diff_number * (float) $detail->price;
                    }),
                    'create_user_id' => user()->id,
                ]);

                $overflow->details()->createMany($positiveDetails->values()->map(function ($detail) use ($check, $overflow) {
                    $number = (float) $detail->diff_number;
                    $price = (float) $detail->price;
                    $resolvedUnit = $this->resolveOverflowUnit((int) $check->warehouse_id, (int) $detail->goods_id, $detail->unit_id, $detail->unit_name);

                    return [
                        'key' => $overflow->key,
                        'date' => $check->date,
                        'status' => 1,
                        'inventory_overflow_id' => $overflow->id,
                        'warehouse_id' => $check->warehouse_id,
                        'goods_id' => $detail->goods_id,
                        'goods_name' => $detail->goods_name,
                        'specs' => $detail->specs,
                        'price' => $price,
                        'number' => $number,
                        'unit_id' => $resolvedUnit['unit_id'],
                        'unit_name' => $resolvedUnit['unit_name'],
                        'amount' => $number * $price,
                        'manufacturer_id' => $detail->manufacturer_id,
                        'manufacturer_name' => $detail->manufacturer_name,
                        'production_date' => $detail->production_date,
                        'expiry_date' => $detail->expiry_date,
                        'batch_code' => $detail->batch_code,
                        'sncode' => $detail->sncode,
                        'remark' => $detail->remark,
                    ];
                })->all());

                $inventoryOverflowId = $overflow->id;
            }

            $check->update([
                'status' => 2,
                'check_user' => user()->id,
                'check_time' => Carbon::now(),
                'inventory_loss_id' => $inventoryLossId,
                'inventory_overflow_id' => $inventoryOverflowId,
            ]);

            $check->details()->update([
                'status' => 2,
            ]);

            DB::commit();

            return $check->fresh(['details']);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 根据当前库存批次拆分报损明细，确保每行都引用真实批次
     *
     * @param  mixed  $detail
     * @return array<int, array<string, mixed>>
     *
     * @throws HisException
     */
    private function buildLossDetailRow(InventoryCheck $check, InventoryLoss $loss, $detail): array
    {
        if (! $detail->inventory_batchs_id) {
            throw new HisException("[{$detail->goods_name}]盘亏明细缺少库存批次，无法生成报损草稿");
        }

        $number = abs((float) $detail->diff_number);
        $price = (float) $detail->price;

        return [
            'key' => $loss->key,
            'date' => $check->date,
            'inventory_loss_id' => $loss->id,
            'warehouse_id' => $check->warehouse_id,
            'department_id' => $check->department_id,
            'goods_id' => $detail->goods_id,
            'goods_name' => $detail->goods_name,
            'specs' => $detail->specs,
            'manufacturer_id' => $detail->manufacturer_id,
            'manufacturer_name' => $detail->manufacturer_name,
            'inventory_batchs_id' => $detail->inventory_batchs_id,
            'batch_code' => $detail->batch_code,
            'production_date' => $detail->production_date,
            'expiry_date' => $detail->expiry_date,
            'unit_id' => $detail->unit_id,
            'unit_name' => $detail->unit_name,
            'price' => $price,
            'number' => $number,
            'amount' => $number * $price,
            'sncode' => $detail->sncode,
            'remark' => $detail->remark,
            'status' => 1,
        ];
    }

    /**
     * 解析报溢明细可用单位，确保不落空
     *
     * @param  mixed  $detailUnitId
     * @param  mixed  $detailUnitName
     * @return array{unit_id:int, unit_name:string}
     *
     * @throws HisException
     */
    private function resolveOverflowUnit(int $warehouseId, int $goodsId, $detailUnitId, $detailUnitName): array
    {
        if ($detailUnitId && $detailUnitName) {
            return [
                'unit_id' => (int) $detailUnitId,
                'unit_name' => (string) $detailUnitName,
            ];
        }

        $batchUnit = InventoryBatchs::query()
            ->select(['unit_id', 'unit_name'])
            ->where('warehouse_id', $warehouseId)
            ->where('goods_id', $goodsId)
            ->where('number', '>', 0)
            ->orderByRaw('case when expiry_date is null then 1 else 0 end')
            ->orderBy('expiry_date')
            ->orderBy('id')
            ->first();

        if ($batchUnit && $batchUnit->unit_id && $batchUnit->unit_name) {
            return [
                'unit_id' => (int) $batchUnit->unit_id,
                'unit_name' => (string) $batchUnit->unit_name,
            ];
        }

        $basicUnit = GoodsUnit::query()
            ->with('unit:id,name')
            ->select(['goods_unit.unit_id'])
            ->where('goods_unit.goods_id', $goodsId)
            ->where('goods_unit.basic', 1)
            ->first();

        if ($basicUnit && $basicUnit->unit_id && $basicUnit->unit?->name) {
            return [
                'unit_id' => (int) $basicUnit->unit_id,
                'unit_name' => (string) $basicUnit->unit->name,
            ];
        }

        throw new HisException('报溢明细单位信息缺失,无法生成草稿');
    }
}
