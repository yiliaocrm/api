<?php

namespace App\Imports;

use App\Models\Address;
use App\Models\Customer;
use App\Models\CustomerEconomic;
use App\Models\CustomerJob;
use App\Models\Medium;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomerImport extends BaseImport
{
    /**
     * 实际业务导入逻辑
     */
    protected function handle(Collection $collection): mixed
    {
        $count = Customer::query()->today()->count() + 1;
        $job = CustomerJob::query()->whereIn('name', $collection->pluck('row_data')->pluck('职业信息')->unique()->toArray())->get();
        $now = Carbon::now()->toDateTimeString();
        $user = User::query()->whereIn('name', $collection->pluck('row_data')->pluck('开发人员')->unique()->merge($collection->pluck('row_data')->pluck('现场咨询')->unique()))->get();
        $medium = Medium::query()->whereIn('name', $collection->pluck('row_data')->pluck('首次来源')->unique()->toArray())->get();
        $address = Address::query()->whereIn('name', $collection->pluck('row_data')->pluck('通讯地址')->unique()->toArray())->get();
        $marital = ['未知' => 1, '未婚' => 2, '已婚' => 3];
        $economic = CustomerEconomic::query()->whereIn('name', $collection->pluck('row_data')->pluck('经济能力')->unique()->toArray())->get();

        $customers = [];
        $customerPhones = [];

        foreach ($collection as $index => $item) {
            try {
                $row = $item->row_data;

                $customer_id = Str::uuid()->toString();
                $phones = explode(',', $row['联系电话']);
                $customer = [
                    'id' => $customer_id,
                    'name' => $row['顾客姓名'],
                    'sex' => (isset($row['顾客性别']) && $row['顾客性别'] == '男') ? 1 : 2,
                    'age' => empty($row['顾客年龄']) ? null : intval($row['顾客年龄']),
                    'idcard' => $row['顾客卡号'] ?? date('Ymd').str_pad($count, 4, '0', STR_PAD_LEFT),
                    'file_number' => $row['档案编号'] ?? null,
                    'sfz' => $row['身份证号'] ?? null,
                    'address_id' => $address->where('name', $row['通讯地址'])->first()->id,
                    'medium_id' => $medium->where('name', $row['首次来源'])->first()->id,
                    'job_id' => $job->where('name', $row['职业信息'])->first()->id ?? null,
                    'birthday' => empty($row['顾客生日']) ? null : $row['顾客生日'],
                    'qq' => $row['联系QQ'] ?? null,
                    'wechat' => $row['微信号码'] ?? null,
                    'marital' => $row['婚姻状况'] ? $marital[$row['婚姻状况']] : null,
                    'economic_id' => $economic->where('name', $row['经济能力'])->first()->id ?? null,
                    'remark' => $row['顾客备注'] ?? null,
                    'user_id' => 1, // 创建人员
                    'ascription' => $user->where('name', $row['开发人员'])->first()->id ?? null,
                    'consultant' => $user->where('name', $row['现场咨询'])->first()->id ?? null,
                    'balance' => $row['账户余额'] ?? 0,
                    'amount' => $row['累计消费'] ?? 0,
                    'created_at' => isset($row['建档时间']) ? Carbon::parse($row['建档时间'])->toDateTimeString() : $now,
                    'updated_at' => $now,
                ];

                // 查询字段
                $customer['keyword'] = implode(',', array_filter(array_merge([
                    $customer['idcard'],
                    $customer['file_number'],
                    implode(',', $phones),
                    $customer['qq'],
                    $customer['wechat'],
                    $customer['sfz'],
                ], parse_pinyin($customer['name']))));

                // 拼接顾客信息
                $customers[] = $customer;

                // 顾客对应的手机信息
                foreach ($phones as $phone) {
                    $customerPhones[] = [
                        'id' => Str::uuid7()->toString(),
                        'phone' => $phone,
                        'customer_id' => $customer['id'],
                    ];
                }

                if (! $row['顾客卡号']) {
                    $count++;
                }
            } catch (\Throwable $e) {
                // 记录错误和行信息到日志或其他存储系统
                Log::error("Error processing row: {$index}", [
                    'row' => $item,
                    'error' => $e->getMessage(),
                ]);

                continue; // Optionally skip to next row
            }
        }

        // 批量插入
        if (! empty($customers)) {
            DB::table('customer')->insert($customers);
            DB::table('customer_phones')->insert($customerPhones);
        }

        return true;
    }

    /**
     * 导入行验证规则
     */
    public function rules(): array
    {
        return [
            '顾客姓名' => 'required',
            '顾客性别' => 'nullable|in:"男","女"',
            '顾客年龄' => 'nullable|integer|between:1,199',
            '联系电话' => 'required|', // 需要加入联系电话验证PhoneValidate
            '顾客卡号' => 'nullable|unique:customer,idcard',
            '档案编号' => 'nullable|unique:customer,file_number',
            '身份证号' => 'nullable|string|max:30',
            '通讯地址' => 'required|exists:address,name',
            '首次来源' => 'required|exists:medium,name',
            '职业信息' => 'nullable|exists:customer_job,name',
            '顾客生日' => 'nullable|date_format:Y-m-d',
            '联系qq' => 'nullable|string',
            '微信号码' => 'nullable|string',
            '婚姻状况' => 'nullable|in:"未知","未婚","已婚"',
            '经济能力' => 'nullable|exists:customer_economic,name',
            '现场咨询' => 'nullable|exists:users,name',
            '开发人员' => 'nullable|exists:users,name',
            '账户余额' => 'nullable|numeric',
            '累计消费' => 'nullable|numeric',
        ];
    }
}
