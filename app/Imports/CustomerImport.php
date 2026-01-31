<?php

namespace App\Imports;

use App\Enums\ImportTaskDetailStatus;
use App\Models\Address;
use App\Models\Customer;
use App\Models\CustomerEconomic;
use App\Models\CustomerJob;
use App\Models\ImportTaskDetail;
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
        $marital = ['未知' => 1, '未婚' => 2, '已婚' => 3];
        $economic = CustomerEconomic::query()->whereIn('name', $collection->pluck('row_data')->pluck('经济能力')->unique()->toArray())->get();

        // 提取所有地址和媒介名称，用于后续查询
        $addressPaths = $collection->pluck('row_data')->pluck('通讯地址')->unique()->filter()->toArray();
        $mediumPaths = $collection->pluck('row_data')->pluck('首次来源')->unique()->filter()->toArray();

        // 提取所有层级中的名称（支持 "福建/泉州市" 这种格式）
        $addressNames = $this->extractNamesFromPaths($addressPaths);
        $mediumNames = $this->extractNamesFromPaths($mediumPaths);

        // 查询所有相关记录（包含 parentid 用于层级匹配）
        $address = Address::query()->whereIn('name', $addressNames)->get();
        $medium = Medium::query()->whereIn('name', $mediumNames)->get();

        $customers = [];
        $customerPhones = [];
        $failedRows = [];

        foreach ($collection as $index => $item) {
            try {
                $row = $item->row_data;
                $errors = [];

                // 业务校验：检查通讯地址是否匹配（支持层级结构如 "福建/泉州市"）
                $addressId = $this->findAddressIdByPath($address, $row['通讯地址'] ?? '');
                if (empty($row['通讯地址'])) {
                    $errors[] = '通讯地址不能为空';
                } elseif ($addressId === null) {
                    $errors[] = "通讯地址 '{$row['通讯地址']}' 不存在，请先添加该地址";
                }

                // 业务校验：检查首次来源是否匹配（支持层级结构如 "运营媒体/线上平台/大众美团"）
                $mediumId = null;
                if (! empty($row['首次来源'])) {
                    $mediumId = $this->findMediumIdByPath($medium, $row['首次来源']);
                    if ($mediumId === null) {
                        $errors[] = "首次来源 '{$row['首次来源']}' 不存在，请先添加该来源";
                    }
                }

                // 如果有业务校验错误，记录为失败行
                if (! empty($errors)) {
                    $failedRows[] = [
                        'id' => $item->id,
                        'import_error_msg' => implode('; ', $errors),
                    ];

                    continue;
                }

                $customer_id = Str::uuid7()->toString();
                $phones = explode(',', $row['联系电话']);
                $customer = [
                    'id' => $customer_id,
                    'name' => $row['顾客姓名'],
                    'sex' => (isset($row['顾客性别']) && $row['顾客性别'] == '男') ? 1 : 2,
                    'age' => empty($row['顾客年龄']) ? null : intval($row['顾客年龄']),
                    'idcard' => $row['顾客卡号'] ?? date('Ymd').str_pad($count, 4, '0', STR_PAD_LEFT),
                    'file_number' => $row['档案编号'] ?? null,
                    'sfz' => $row['身份证号'] ?? null,
                    'address_id' => $addressId,
                    'medium_id' => $mediumId,
                    'job_id' => $job->where('name', $row['职业信息'])->first()?->id ?? null,
                    'birthday' => empty($row['顾客生日']) ? null : $row['顾客生日'],
                    'qq' => $row['联系QQ'] ?? null,
                    'wechat' => $row['微信号码'] ?? null,
                    'marital' => $row['婚姻状况'] ? $marital[$row['婚姻状况']] : null,
                    'economic_id' => $economic->where('name', $row['经济能力'])->first()?->id ?? null,
                    'remark' => $row['顾客备注'] ?? null,
                    'user_id' => 1, // 创建人员
                    'ascription' => $user->where('name', $row['开发人员'])->first()?->id ?? null,
                    'consultant' => $user->where('name', $row['现场咨询'])->first()?->id ?? null,
                    'balance' => $row['账户余额'] ?? 0,
                    'amount' => $row['累计消费'] ?? 0,
                    'first_time' => empty($row['初诊时间']) ? null : Carbon::parse($row['初诊时间'])->toDateTimeString(),
                    'last_time' => empty($row['最近光临']) ? null : Carbon::parse($row['最近光临'])->toDateTimeString(),
                    'last_followup' => empty($row['最近回访']) ? null : Carbon::parse($row['最近回访'])->toDateTimeString(),
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

                // 将异常行记录为失败
                $failedRows[] = [
                    'id' => $item->id,
                    'import_error_msg' => '导入异常：'.$e->getMessage(),
                ];

                continue;
            }
        }

        // 收集成功的行ID
        $successIds = $collection->pluck('id')->diff(array_column($failedRows, 'id'))->toArray();

        // 更新成功行的状态
        if (! empty($successIds)) {
            ImportTaskDetail::query()->whereIn('id', $successIds)->update([
                'status' => ImportTaskDetailStatus::SUCCESS,
            ]);
        }

        // 更新失败行的状态
        if (! empty($failedRows)) {
            foreach ($failedRows as $failedRow) {
                ImportTaskDetail::query()->where('id', $failedRow['id'])->update([
                    'status' => ImportTaskDetailStatus::FAILED,
                    'import_error_msg' => $failedRow['import_error_msg'],
                ]);
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
            '通讯地址' => 'nullable|string',
            '首次来源' => 'nullable|string',
            '职业信息' => 'nullable|exists:customer_job,name',
            '顾客生日' => 'nullable|date_format:Y-m-d',
            '联系QQ' => 'nullable|string',
            '微信号码' => 'nullable|string',
            '婚姻状况' => 'nullable|in:"未知","未婚","已婚"',
            '经济能力' => 'nullable|exists:customer_economic,name',
            '现场咨询' => 'nullable|exists:users,name',
            '开发人员' => 'nullable|exists:users,name',
            '账户余额' => 'nullable|numeric',
            '累计消费' => 'nullable|numeric',
            '初诊时间' => 'nullable|date',
            '最近光临' => 'nullable|date',
            '最近回访' => 'nullable|date',
        ];
    }

    /**
     * 从路径列表中提取所有层级名称
     */
    protected function extractNamesFromPaths(array $paths): array
    {
        $names = [];
        foreach ($paths as $path) {
            if (empty($path)) {
                continue;
            }
            $levels = array_filter(explode('/', $path), fn ($value) => trim($value) !== '');
            foreach ($levels as $level) {
                $names[] = trim($level);
            }
        }

        return array_unique($names);
    }

    /**
     * 根据路径查找地址ID（支持层级结构如 "福建/泉州市"）
     */
    protected function findAddressIdByPath(\Illuminate\Support\Collection $addressCollection, string $path): ?int
    {
        if (empty($path)) {
            return null;
        }

        // 解析层级结构
        $levels = array_filter(explode('/', $path), fn ($value) => trim($value) !== '');
        $parentId = 0;

        foreach ($levels as $levelName) {
            $levelName = trim($levelName);

            // 在当前层级中查找匹配的名称和父ID
            $address = $addressCollection
                ->where('name', $levelName)
                ->where('parentid', $parentId)
                ->first();

            if ($address === null) {
                return null;
            }

            $parentId = $address->id;
        }

        return $parentId;
    }

    /**
     * 根据路径查找媒介ID（支持层级结构如 "运营媒体/线上平台/大众美团"）
     */
    protected function findMediumIdByPath(\Illuminate\Support\Collection $mediumCollection, string $path): ?int
    {
        if (empty($path)) {
            return null;
        }

        // 解析层级结构
        $levels = array_filter(explode('/', $path), fn ($value) => trim($value) !== '');
        $parentId = 0;

        foreach ($levels as $levelName) {
            $levelName = trim($levelName);

            // 在当前层级中查找匹配的名称和父ID
            $medium = $mediumCollection
                ->where('name', $levelName)
                ->where('parentid', $parentId)
                ->first();

            if ($medium === null) {
                return null;
            }

            $parentId = $medium->id;
        }

        return $parentId;
    }
}
