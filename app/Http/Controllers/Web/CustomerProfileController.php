<?php

namespace App\Http\Controllers\Web;

//use App\Models\Sms;
use App\Models\Erkai;
use App\Models\Cashier;
use App\Models\Followup;
use App\Models\Treatment;
use App\Models\Customer;
use App\Models\Consultant;
use App\Models\Reservation;
use App\Models\Appointment;
use App\Models\CouponDetail;
use App\Models\CustomerLog;
use App\Models\CustomerTalk;
use App\Models\CustomerPhoto;
use App\Models\CustomerGoods;
use App\Models\CustomerPhone;
use App\Models\CustomerProduct;
use App\Models\CustomerQufriend;
use App\Models\CustomerPhoneView;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\CustomerProfileRequest;

class CustomerProfileController extends Controller
{
    /**
     * 用户档案
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function info(CustomerProfileRequest $request): JsonResponse
    {
        $customer = Customer::query()->find(
            $request->input('customer_id')
        );
        $customer->load([
            'tags',
            'items',
            'phones',
            'medium:id,name',
            'doctorUser:id,name',
            'serviceUser:id,name',
            'consultantUser:id,name',
            'ascriptionUser:id,name',
        ]);
        return response_success($customer);
    }

    /**
     * 顾客手机
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function phone(CustomerProfileRequest $request): JsonResponse
    {
        $id    = $request->input('id');
        $show  = $request->input('show');
        $query = CustomerPhone::query()->where('customer_id', $request->input('customer_id'));

        if (!user()->hasAnyAccess(['superuser', 'customer.phone']) && $show) {
            return response_error(msg: '没有权限查看顾客手机号码');
        }

        // 显示原始手机号码
        if ($show) {
            CustomerPhone::$showOriginalPhone = true;
        }

        // 查询顾客单个号码或全部号码
        if ($id) {
            $data = $query->where('id', $id)->first();
        } else {
            $data = $query->get();
        }

        // 记录查看日志
        if ($show && $id) {
            CustomerPhoneView::query()->create([
                'phone'       => $data->phone,
                'user_id'     => user()->id,
                'customer_id' => $request->input('customer_id'),
            ]);
        }

        return response_success($data);
    }

    /**
     * 顾客概览
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function overview(CustomerProfileRequest $request): JsonResponse
    {
        $customer = Customer::query()->find(
            $request->input('customer_id')
        );
        $customer->load([
            'job:id,name',
            'tags',
            'phones',
            'economic:id,name',
            'consultantUser:id,name',
            'ascriptionUser:id,name',
            'referrerUser:id,name',
            'referrerCustomer:id,name,idcard',
        ]);
        $customer->medium_name  = get_medium_name($customer->medium_id, true);
        $customer->address_name = get_address_name($customer->address_id, true);
        return response_success($customer);
    }

    /**
     * 顾客日志
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function log(CustomerProfileRequest $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = CustomerLog::query()
            ->with(['user:id,name'])
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 短信记录
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function sms(CustomerProfileRequest $request): JsonResponse
    {
//        $customer = Customer::query()->find(request('customer_id'));
//        $phone    = explode(',', $customer->getRawOriginal('phone'));
//        $rows     = $request->input('rows', 10);
//        $sort     = $request->input('sort', 'created_at');
//        $order    = $request->input('order', 'desc');
//        $query    = Sms::query()
//            ->with(['user:id,name'])
//            ->whereIn('phone', $phone)
//            ->orderBy($sort, $order)
//            ->paginate($rows);
//
//        return response_success([
//            'rows'  => $query->items(),
//            'total' => $query->total()
//        ]);
        return response_success();
    }

    /**
     * 顾客沟通信息
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function talk(CustomerProfileRequest $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $query = CustomerTalk::query()
            ->with('talk')
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy('created_at', 'desc')
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 网电咨询记录
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function reservation(CustomerProfileRequest $request): JsonResponse
    {
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $query = Reservation::query()
            ->with([
                'reservationType:id,name',
                'reservationItems:id,name',
                'reservationAscription:id,name',
                'reservationUser:id,name'
            ])
            ->where('customer_id', $request->input('customer_id'))
            ->when($request->input('status'), fn(Builder $query) => $query->where('status', $request->input('status')))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 顾客现场咨询记录
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function consultant(CustomerProfileRequest $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $query = Consultant::query()
            ->with([
                'orders' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'user:id,name',
                'medium:id,name',
                'department:id,name',
                'receptionType:id,name',
                'receptionItems:id,name',
                'consultantUser:id,name',
                'doctorInfo:id,name',
                'receptionInfo:id,name'
            ])
            ->where('customer_id', $request->input('customer_id'))
            ->when($request->input('status'), fn(Builder $query) => $query->where('status', $request->input('status')))
            ->orderBy('created_at', 'desc')->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 成交项目明细
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function product(CustomerProfileRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $status  = $request->input('status');
        $builder = CustomerProduct::query()
            ->with([
                'medium:id,name',
                'cashier:id,cashierable_type',
            ])
            ->where('customer_id', $request->input('customer_id'))
            ->when($status, fn(Builder $query) => $query->where('status', $status))
            ->orderBy($sort, $order);

        $query  = $builder->clone()->paginate($rows);
        $footer = [
            [
                'product_name' => '页小计:',
                'income'       => collect($query->items())->sum('income'),
                'payable'      => collect($query->items())->sum('payable'),
                'arrearage'    => collect($query->items())->sum('arrearage'),
            ],
            [
                'product_name' => '总合计:',
                'income'       => floatval($builder->clone()->sum('income')),
                'payable'      => floatval($builder->clone()->sum('payable')),
                'arrearage'    => floatval($builder->clone()->sum('arrearage')),
            ]
        ];

        return response_success([
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ]);
    }

    /**
     * 顾客项目明细
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function goods(CustomerProfileRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $builder = CustomerGoods::query()
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy($sort, $order);

        $query  = $builder->clone()->paginate($rows);
        $footer = [
            [
                'goods_name' => '页小计:',
                'payable'    => collect($query->items())->sum('payable'),
                'income'     => collect($query->items())->sum('income'),
            ],
            [
                'goods_name' => '总合计:',
                'payable'    => floatval($builder->clone()->sum('payable')),
                'income'     => floatval($builder->clone()->sum('income')),
            ]
        ];

        return response_success([
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ]);
    }

    /**
     * 治疗记录
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function treatment(CustomerProfileRequest $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $query = Treatment::query()
            ->with([
                'user:id,name',
                'treatmentParticipants.user:id,name',
            ])
            ->when($request->input('status'), fn(Builder $query) => $query->where('status', $request->input('status')))
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy('created_at', 'desc')
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 返回顾客回访信息
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function followup(CustomerProfileRequest $request): JsonResponse
    {
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $query = Followup::query()
            ->with([
                'user:id,name',
                'followupUserInfo:id,name',
                'followupTool:id,name',
                'followupType:id,name',
                'executeUserInfo:id,name'
            ])
            ->when($request->input('status'), fn(Builder $query) => $query->where('status', $request->input('status')))
            ->when($request->input('user_id'), fn(Builder $query) => $query->where('user_id', $request->input('user_id')))
            ->when($request->input('execute_user'), fn(Builder $query) => $query->where('execute_user', $request->input('execute_user')))
            ->when($request->input('followup_user'), fn(Builder $query) => $query->where('followup_user', $request->input('followup_user')))
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 二开记录
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function erkai(CustomerProfileRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $builder = Erkai::query()
            ->with([
                'user:id,name',
                'details',
                'department:id,name'
            ])
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy($sort, $order);

        $query  = $builder->clone()->paginate($rows);
        $footer = [
            [
                'status'    => '页小计:',
                'income'    => collect($query->items())->sum('income'),
                'payable'   => collect($query->items())->sum('payable'),
                'arrearage' => collect($query->items())->sum('arrearage'),
            ],
            [
                'status'    => '总合计:',
                'income'    => floatval($builder->clone()->sum('income')),
                'payable'   => floatval($builder->clone()->sum('payable')),
                'arrearage' => floatval($builder->clone()->sum('arrearage')),
            ]
        ];

        return response_success([
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ]);
    }

    /**
     * 对比照
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function photo(CustomerProfileRequest $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $flag  = $request->input('flag');
        $query = CustomerPhoto::query()
            ->with([
                'details' => function ($query) {
                    $query->orderBy('created_at', 'asc');
                }
            ])
            ->when($flag !== 'all', fn(Builder $query) => $query->where('flag', $flag))
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy('created_at', 'desc')
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 预约记录
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function appointment(CustomerProfileRequest $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $query = Appointment::query()
            ->with([
                'department:id,name',
                'consultant:id,name',
                'doctor:id,name',
                'createUser:id,name',
            ])
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy('created_at', 'desc')
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 顾客卡券
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function coupons(CustomerProfileRequest $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $query = CouponDetail::query()
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy('created_at', 'desc')
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 亲友关系
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function qufriend(CustomerProfileRequest $request): JsonResponse
    {
        $qufriend = CustomerQufriend::query()
            ->with([
                'relatedCustomer:id,name,idcard,sex',
                'qufriend:id,name'
            ])
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy('id')
            ->get();
        return response_success($qufriend);
    }

    /**
     * 收银记录
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function cashier(CustomerProfileRequest $request): JsonResponse
    {
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $rows    = $request->input('rows', 10);
        $builder = Cashier::query()
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy($sort, $order);

        $query = $builder
            ->clone()
            ->with([
                'user:id,name',
                'operatorUser:id,name'
            ])
            ->paginate($rows);

        $header = [
            'coupon'    => floatval($builder->clone()->sum('coupon')),
            'income'    => floatval($builder->clone()->sum('income')),
            'deposit'   => floatval($builder->clone()->sum('deposit')),
            'payable'   => floatval($builder->clone()->sum('payable')),
            'arrearage' => floatval($builder->clone()->sum('arrearage')),
            'refund'    => floatval($builder->clone()->where('cashierable_type', 'App\\Models\\CashierRefund')->sum('income')),
        ];

        return response_success([
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'header' => $header
        ]);
    }
}
