<?php

namespace App\Http\Controllers\Web;

use App\Models\ExportTask;
use App\Models\UsersLogin;
use App\Models\CustomerLog;
use App\Models\CustomerPhoneView;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\LogRequest;
use Illuminate\Database\Eloquent\Builder;

class LogController extends Controller
{
    /**
     * 登录日志
     * @param LogRequest $request
     * @return JsonResponse
     */
    public function login(LogRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'id');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');
        $user_id = $request->input('user_id');

        $query = UsersLogin::query()
            ->with([
                'user:id,name'
            ])
            ->when($user_id, fn(Builder $query) => $query->where('user_id', $user_id))
            ->when($keyword, fn(Builder $query) => $query->whereAny(['ip', 'country', 'province', 'city', 'browser', 'platform', 'remark', 'fingerprint'], 'like', "%{$keyword}%"))
            ->whereBetween('created_at', [
                Carbon::parse($request->input('created_at.0'))->startOfDay(),
                Carbon::parse($request->input('created_at.1'))->endOfDay()
            ])
            ->orderBy($sort, $order)
            ->paginate($rows);

        $query->append(['type_text']);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 顾客修改日志
     * @param LogRequest $request
     * @return JsonResponse
     */
    public function customer(LogRequest $request): JsonResponse
    {
        $rows        = $request->input('rows', 10);
        $sort        = $request->input('sort', 'created_at');
        $order       = $request->input('order', 'desc');
        $action      = $request->input('action');
        $user_id     = $request->input('user_id');
        $customer_id = $request->input('customer_id');

        $query = CustomerLog::query()
            ->with([
                'user:id,name',
                'customer:id,name,idcard',
            ])
            ->whereBetween('created_at', [
                Carbon::parse($request->input('created_at.0'))->startOfDay(),
                Carbon::parse($request->input('created_at.1'))->endOfDay()
            ])
            ->when($action, fn(Builder $query) => $query->where('action', $action))
            ->when($user_id, fn(Builder $query) => $query->where('user_id', $user_id))
            ->when($customer_id, fn(Builder $query) => $query->where('customer_id', $customer_id))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 导出数据记录
     * @param LogRequest $request
     * @return JsonResponse
     */
    public function export(LogRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $status  = $request->input('status');
        $user_id = $request->input('user_id');

        $query = ExportTask::query()
            ->with([
                'user:id,name',
            ])
            ->whereBetween('created_at', [
                Carbon::parse($request->input('created_at.0'))->startOfDay(),
                Carbon::parse($request->input('created_at.1'))->endOfDay()
            ])
            ->when($status, fn(Builder $query) => $query->where('status', $status))
            ->when($user_id, fn(Builder $query) => $query->where('user_id', $user_id))
            ->orderBy($sort, $order)
            ->paginate($rows);

        $query->append(['status_text']);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 号码查看记录
     * @param LogRequest $request
     * @return JsonResponse
     */
    public function phone(LogRequest $request): JsonResponse
    {
        $rows        = $request->input('rows', 10);
        $sort        = $request->input('sort', 'id');
        $order       = $request->input('order', 'desc');
        $phone       = $request->input('phone');
        $user_id     = $request->input('user_id');
        $customer_id = $request->input('customer_id');

        $query = CustomerPhoneView::query()
            ->with([
                'user:id,name',
                'customer:id,name,idcard',
            ])
            ->whereBetween('created_at', [
                Carbon::parse($request->input('created_at.0'))->startOfDay(),
                Carbon::parse($request->input('created_at.1'))->endOfDay()
            ])
            ->when($phone, fn(Builder $query) => $query->where('phone', 'like', "%{$phone}%"))
            ->when($user_id, fn(Builder $query) => $query->where('user_id', $user_id))
            ->when($customer_id, fn(Builder $query) => $query->where('customer_id', $customer_id))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }
}
