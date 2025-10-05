<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Web\CustomerBatchRequest;

class CustomerBatchController extends Controller
{
    /**
     * 批量修改主治医生
     * @param CustomerBatchRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function doctor(CustomerBatchRequest $request): JsonResponse
    {
        $ids   = $request->input('ids', []);
        $isall = $request->input('isall', false);

        DB::beginTransaction();
        try {

            // 勾选部分顾客更新
            if (!empty($ids) && !$isall) {
                $request->updateOwnershipByIds('doctor_id');
            }

            // 全选更改开发员
            if (empty($ids) && $isall) {
                $request->updateOwnershipByAll('doctor_id');
            }

            DB::commit();
            return response_success();
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 批量修改专属客服
     * @param CustomerBatchRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function service(CustomerBatchRequest $request): JsonResponse
    {
        $ids   = $request->input('ids', []);
        $isall = $request->input('isall', false);

        DB::beginTransaction();
        try {

            // 勾选部分顾客更新
            if (!empty($ids) && !$isall) {
                $request->updateOwnershipByIds('service_id');
            }

            // 全选更改开发员
            if (empty($ids) && $isall) {
                $request->updateOwnershipByAll('service_id');
            }

            DB::commit();
            return response_success();
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 批量修改开发人
     * @param CustomerBatchRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function ascription(CustomerBatchRequest $request): JsonResponse
    {
        $ids   = $request->input('ids', []);
        $isall = $request->input('isall', false);

        DB::beginTransaction();
        try {

            // 勾选部分顾客更新
            if (!empty($ids) && !$isall) {
                $request->updateOwnershipByIds('ascription');
            }

            // 全选更改开发员
            if (empty($ids) && $isall) {
                $request->updateOwnershipByAll('ascription');
            }

            DB::commit();
            return response_success();
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 批量更改归属销售顾问
     * @param CustomerBatchRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function consultant(CustomerBatchRequest $request): JsonResponse
    {
        $ids   = $request->input('ids', []);
        $isall = $request->input('isall', false);

        DB::beginTransaction();
        try {

            // 勾选部分顾客更新
            if (!empty($ids) && !$isall) {
                $request->updateOwnershipByIds('consultant');
            }

            // 全选更改开发员
            if (empty($ids) && $isall) {
                $request->updateOwnershipByAll('consultant');
            }

            DB::commit();
            return response_success();
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 批量设置回访
     * @param CustomerBatchRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function followup(CustomerBatchRequest $request): JsonResponse
    {
        $ids   = $request->input('ids', []);
        $isall = $request->input('isall', false);

        DB::beginTransaction();
        try {

            // 勾选部分顾客更新
            if (!empty($ids) && !$isall) {
                $request->setFollowupByIds();
            }

            // 全选设置回访
            if (empty($ids) && $isall) {
                $request->setFollowupByAll();
            }

            DB::commit();
            return response_success();

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 批量设置标签
     * @param CustomerBatchRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function tags(CustomerBatchRequest $request): JsonResponse
    {
        $ids   = $request->input('ids', []);
        $rules = $request->input('rules');
        $isall = $request->input('isall', false);

        DB::beginTransaction();
        try {

            // 勾选部分顾客新增标签
            if (!empty($ids) && $rules === 'add' && !$isall) {
                $request->addTagsByIds();
            }

            // 勾选部分顾客删除标签
            if (!empty($ids) && $rules === 'remove' && !$isall) {
                $request->removeTagsByIds();
            }

            // 全选新增标签
            if (empty($ids) && $rules === 'add' && $isall) {
                $request->addTagsByAll();
            }

            // 全选删除标签
            if (empty($ids) && $rules === 'remove' && $isall) {
                $request->removeTagsByAll();
            }

            DB::commit();
            return response_success();
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 加入分组
     * @param CustomerBatchRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function joinGroup(CustomerBatchRequest $request): JsonResponse
    {
        $ids   = $request->input('ids', []);
        $isall = $request->input('isall', false);

        DB::beginTransaction();
        try {

            // 勾选部分
            if (!empty($ids) && !$isall) {
                $request->joinGroupByIds();
            }

            // 全选
            if (empty($ids) && $isall) {
                $request->joinGroupByAll();
            }

            DB::commit();
            return response_success();
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 更改分组
     * @param CustomerBatchRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function changeGroup(CustomerBatchRequest $request): JsonResponse
    {
        $ids   = $request->input('ids', []);
        $isall = $request->input('isall', false);

        DB::beginTransaction();
        try {

            // 勾选部分
            if (!empty($ids) && !$isall) {
                $request->changeGroupByIds();
            }

            // 全选
            if (empty($ids) && $isall) {
                $request->changeGroupByAll();
            }

            DB::commit();
            return response_success();
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 移出分组
     * @param CustomerBatchRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function removeGroup(CustomerBatchRequest $request): JsonResponse
    {
        $ids   = $request->input('ids', []);
        $isall = $request->input('isall', false);

        DB::beginTransaction();
        try {

            // 勾选部分
            if (!empty($ids) && !$isall) {
                $request->removeGroupByIds();
            }

            // 全选
            if (empty($ids) && $isall) {
                $request->removeGroupByAll();
            }

            DB::commit();
            return response_success();
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 批量发送短信
     * @param CustomerBatchRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function sms(CustomerBatchRequest $request): JsonResponse
    {
        $ids   = $request->input('ids', []);
        $isall = $request->input('isall', false);

        try {
            // 勾选部分顾客发送短信
            if (!empty($ids) && !$isall) {
                $request->sendSmsByIds();
            }

            // 全选发送短信
            if (empty($ids) && $isall) {
                $request->sendSmsByAll();
            }

            return response_success('短信发送任务已提交到队列，正在异步处理中');
        } catch (Exception $e) {
            throw new HisException($e->getMessage());
        }
    }
}
