<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use App\Observers\CustomerObserver;
use App\Traits\QueryConditionsTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([CustomerObserver::class])]
class Customer extends Authenticatable
{
    use HasUuids, HasApiTokens, QueryConditionsTrait;

    protected $table = 'customer';
    protected $hidden = ['keyword'];
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount'        => 'float',
            'balance'       => 'float',
            'integral'      => 'float',
            'arrearage'     => 'float',
            'total_payment' => 'float'
        ];
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    /**
     * 媒介来源
     * @return BelongsTo
     */
    public function medium(): BelongsTo
    {
        return $this->belongsTo(Medium::class);
    }

    /**
     * 职业信息
     * @return BelongsTo
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(CustomerJob::class, 'job_id', 'id');
    }

    /**
     * 经济能力
     * @return BelongsTo
     */
    public function economic(): BelongsTo
    {
        return $this->belongsTo(CustomerEconomic::class, 'economic_id', 'id');
    }

    /**
     * 生命周期
     * @return MorphMany
     */
    public function cycle(): MorphMany
    {
        return $this->morphMany(CustomerLifeCycle::class, 'cycle');
    }

    /**
     * 顾客日志
     * @return MorphMany
     */
    public function log(): MorphMany
    {
        return $this->morphMany(CustomerLog::class, 'logable');
    }

    /**
     * 预存款变动明细
     * @return BelongsToMany
     */
    public function depositDetails(): BelongsToMany
    {
        return $this->belongsToMany(CustomerDepositDetail::class);
    }

    /**
     * 回访记录
     * @return HasMany
     */
    public function followup(): HasMany
    {
        return $this->hasMany(Followup::class);
    }

    /**
     * 回访记录
     * @return HasMany
     */
    public function followups(): HasMany
    {
        return $this->hasMany(Followup::class);
    }

    /**
     * 治疗记录
     * @return HasMany
     */
    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    /**
     * 顾客物品明细
     * @return HasMany
     */
    public function goods(): HasMany
    {
        return $this->hasMany(CustomerGoods::class);
    }

    /**
     * 网电咨询记录
     * @return HasMany
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * 分诊记录
     * @return HasMany
     */
    public function receptions(): HasMany
    {
        return $this->hasMany(Reception::class);
    }

    /**
     * 积分记录
     * @return HasMany
     */
    public function integrals(): HasMany
    {
        return $this->hasMany(Integral::class);
    }

    /**
     * 已购卡券列表
     * @return HasMany
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(CouponDetail::class);
    }

    /**
     * 顾客标签
     * @return BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tags::class, 'customer_tags', 'customer_id', 'tags_id')
            ->withPivot('created_at', 'updated_at')
            ->withTimestamps();
    }

    /**
     * 咨询项目
     * @return BelongsToMany
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'customer_items', 'customer_id', 'item_id')
            ->withPivot('created_at', 'updated_at')
            ->withTimestamps();
    }

    /**
     * 顾客主号码
     * 默认显示本人
     * @return HasOne
     */
    public function phone(): HasOne
    {
        return $this->hasOne(CustomerPhone::class)
            ->orderBy('relation_id')
            ->oldest();
    }

    /**
     * 顾客手机号码
     * @return HasMany
     */
    public function phones(): HasMany
    {
        return $this->hasMany(CustomerPhone::class);
    }

    /**
     * 预约记录
     * @return HasMany
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * 成交项目明细
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(CustomerProduct::class)->orderBy('created_at', 'desc');
    }

    /**
     * 业绩表
     * @return HasMany
     */
    public function salesPerformances(): HasMany
    {
        return $this->hasMany(SalesPerformance::class);
    }

    /**
     * 创建人员
     * @return BelongsTo
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 开发人员
     * @return BelongsTo
     */
    public function ascriptionUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ascription');
    }

    /**
     * 现场咨询
     * @return BelongsTo
     */
    public function consultantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultant');
    }

    /**
     * 专属客服
     * @return BelongsTo
     */
    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'service_id');
    }

    /**
     * 主治医生
     * @return BelongsTo
     */
    public function doctorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * 推荐员工
     * @return BelongsTo
     */
    public function referrerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /**
     * 推荐客户
     * @return BelongsTo
     */
    public function referrerCustomer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referrer_customer_id');
    }

    public function scopeToday($query)
    {
        return $query->whereBetween('created_at', [
            Carbon::today(),
            Carbon::today()->endOfDay()
        ]);
    }

    /**
     * 合并顾客
     * @param string $customer_id 被合并顾客id
     */
    public function merge(string $customer_id): void
    {
        $tables = $this->getTables();

        foreach ($tables as $table) {
            DB::table($table)->where('customer_id', $customer_id)->update(['customer_id' => $this->id]);
        }

        // 被合并客户
        $customer = self::find($customer_id);

        // 合并customer表信息
        $this->update([
            'total_payment'   => bcadd($this->total_payment, $customer->total_payment, 4),
            'balance'         => bcadd($this->balance, $customer->balance, 4),
            'amount'          => bcadd($this->amount, $customer->amount, 4),
            'arrearage'       => bcadd($this->arrearage, $customer->arrearage, 4),
            'integral'        => bcadd($this->integral, $customer->integral, 4),
            'expend_integral' => bcadd($this->expend_integral, $customer->expend_integral, 4),
        ]);

        // 删掉被合并的顾客
        $customer->delete();
    }

    /**
     * 删除顾客
     */
    public function remove(): void
    {
        $tables = $this->getTables();

        foreach ($tables as $table) {
            DB::table($table)->where('customer_id', $this->id)->delete();
        }

        $this->delete();
    }

    /**
     * 获取所有关联表
     * @return string[]
     */
    public function getTables(): array
    {
        return [
            'accounts_receivable',
            'cashier',
            'cashier_arrearage',
            'cashier_arrearage_detail',
            'cashier_coupons',
            'cashier_detail',
            'cashier_invoice_details',
            'cashier_invoices',
            'cashier_pay',
            'cashier_refund',
            'cashier_refund_detail',
            'cashier_retail',
            'cashier_retail_detail',
            'consumable',
            'consumable_detail',
            'coupon_detail_histories',
            'coupon_details',
            'customer_arrearage_details',
            'customer_deposit_details',
            'customer_goods',
            'customer_group_details',
            'customer_items',
            'customer_life_cycle',
            'customer_log',
            'customer_photo_details',
            'customer_photos',
            'customer_product',
            'customer_tags',
            'customer_talk',
            'customer_wechats',
            'erkai',
            'erkai_detail',
            'followup',
            'integral',
            'outpatient_emr',
            'outpatient_prescription',
            'customer_phones',
            'reception',
            'reception_order',
            'recharge',
            'reservation',
            'retail_outbound',
            'retail_outbound_detail',
            'sales_performance',
            'treatment',
            'appointments'
        ];
    }

    /**
     * 生成顾客搜索关键词
     * @param array $data 顾客信息
     * @param array $phone 联系电话
     * @return string
     */
    public static function generateKeyword(array $data, array $phone): string
    {
        $keyword = [
            implode(',', parse_pinyin($data['name'])),
            $data['sfz'] ?? '',
            $data['qq'] ?? '',
            $data['wechat'] ?? '',
            $data['idcard'] ?? '',
            $data['file_number'] ?? '',
            implode(',', $phone),
        ];
        return implode(',', array_filter($keyword));
    }
}
