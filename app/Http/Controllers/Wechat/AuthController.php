<?php

namespace App\Http\Controllers\Wechat;

use App\Models\Store;
use App\Models\PersonalAccessToken;
use App\Models\CustomerPhone;
use App\Models\Customer;
use App\Models\CustomerWechat;
use App\Services\MiniAppService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wechat\LoginRequest;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AuthController extends Controller
{
    /**
     *
     * @return JsonResponse
     */
    public function config(): JsonResponse
    {
        // 所有门店配置
        $stores = Store::query()
            ->select(['name', 'phone', 'address', 'business_start', 'business_end', 'slot_duration'])
            ->get();
        return response_success([
            'stores' => $stores,
        ]);
    }

    /**
     * 登录
     * @param LoginRequest $request
     * @param MiniAppService $service
     * @return JsonResponse
     * @throws TransportExceptionInterface
     */
    public function login(LoginRequest $request, MiniAppService $service): JsonResponse
    {
        $session  = $service->codeToSession($request->input('login_code'));
        $response = $service->getUserPhoneNumber($request->input('phone_code'));
        $phone    = $response['phone_info']['purePhoneNumber'];

        // 注册微信用户
        $wechat = CustomerWechat::query()->where('open_id', $session['openid'])->first();
        if (!$wechat) {
            $customer = $this->findCustomerOrCreate($phone);
            $wechat   = CustomerWechat::query()->create([
                'customer_id' => $customer->id,
                'open_id'     => $session['openid'],
                'phone'       => $phone,
                'nickname'    => '小程序用户',
                'avatar'      => '',
                'country'     => '',
                'province'    => '',
                'city'        => '',
            ]);
        }

        // 删除旧的token
        PersonalAccessToken::query()
            ->where('tokenable_id', $wechat->customer_id)
            ->where('tokenable_type', Customer::class)
            ->delete();

        return response_success([
            'token'   => $wechat->customer->createToken('wechat')->plainTextToken,
            'profile' => [
                'nickname' => $wechat->nickname,
                'phone'    => $wechat->phone,
            ]
        ]);
    }

    /**
     * 退出登录
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        $user = auth('customer')->user();
        $user?->tokens()->delete();
        return response_success();
    }

    /**
     * 查找用户或创建
     * @param $phone
     * @return Builder|Builder[]|Collection|Model|null
     */
    protected function findCustomerOrCreate($phone): Model|Collection|Builder|array|null
    {
        $r = CustomerPhone::query()->where('phone', $phone)->first();
        if ($r) {
            return Customer::query()->find($r->customer_id);
        }

        $data = [
            'name'       => '小程序用户',
            'sex'        => 3,
            'phone'      => explode(',', $phone),
            'idcard'     => date('Ymd') . str_pad((Customer::query()->today()->count() + 1), 4, '0', STR_PAD_LEFT),
            'medium_id'  => 2,
            'address_id' => 1,
            'user_id'    => 1, // 创建人员
            'ascription' => 1, // 开发人员
        ];

        // 生成搜索关键词
        $data['keyword'] = implode(',', array_filter(array_merge([
            $data['idcard'],
            implode(',', $data['phone']),
        ], parse_pinyin($data['name']))));

        return Customer::query()->create($data);
    }


}
