<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Ramsey\Uuid\Uuid;
use App\Models\Customer;


class Outpatient extends BaseModel
{
    protected $table = 'reception';
    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'items' => 'array',
        ];
    }


    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($reception) {
            $reception->id = Uuid::uuid7()->toString();
        });

        static::created(function ($reception) {

            # 写入日志
            $reception->log()->create([
                'customer_id' => $reception->customer_id
            ]);

            # 写入生命周期
            $reception->cycle()->create([
                'name'        => '顾客上门',
                'customer_id' => $reception->customer_id
            ]);

            # 更新预约表
            $reception->reservation()->whereNull('reception_id')->update([
                'reception_id' => $reception->id,
                'status'       => 2, // 上门
                'cometime'     => Carbon::now()->toDateTimeString()
            ]);


            $customer = Customer::find($reception->customer_id);

            # 指定现场咨询
            if ($customer->consultant == 0) {
                $customer->consultant = $reception->consultant;
            }

            # 更新第一次上门时间
            if (!$customer->first_time) {
                $customer->first_time = Carbon::now()->toDateTimeString();
            }

            # 最后上门时间
            $customer->last_time = Carbon::now()->toDateTimeString();
            $customer->save();

            # 后续增加 [消息通知] 模块
        });

        static::saved(function ($reception) {
            # 关联预约项目
            $reception->items()->sync($reception->items);
            // 更新咨询项目
            // Customer::find($reception->customer_id)->updateItems();
            // $reception->customer->updateItems();

            # 沟通信息
            if ($reception->remark && $reception->talk->isEmpty()) {
                $reception->talk()->create([
                    'name'        => '咨询情况',
                    'customer_id' => $reception->customer_id
                ]);
            }
        });

        static::updated(function ($reception) {
            if ($reception->isDirty()) {
                $reception->log()->create([
                    'customer_id' => $reception->customer_id,
                    'original'    => $reception->getRawOriginal(),
                    'dirty'       => $reception->getDirty()
                ]);
            }
        });

        # 删除操作.
        static::deleting(function ($reception) {
            # 反向更新预约表
            $reception->reservation()->where('reception_id', $reception->id)->update([
                'reception_id' => null,
                'status'       => 1,
                'cometime'     => null
            ]);

            $customer = $reception->customer;

            # 反向更新 现场咨询
            $count = Reception::where('customer_id', $reception->customer_id)
                ->where('consultant', $reception->consultant)
                ->where('id', '<>', $reception->id)->count('id');

            if (!$count) {
                $customer->consultant = 0;
            }

            # 反向更新 上门时间
            if ($customer->first_time == $customer->last_time && $customer->last_time == $reception->created_at) {
                $customer->first_time = null;
                $customer->last_time  = null;
            }

            $customer->save();
        });

        static::deleted(function ($reception) {
            # 删除关联项目
            $reception->items()->detach();

            # 删除生命周期
            $reception->cycle()->delete();

            # 删除沟通信息
            $reception->talk()->delete();

            # 写入日志
            $reception->log()->create([
                'customer_id' => $reception->customer_id
            ]);
        });
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Item', 'reception_items', 'reception_id');
    }

    /**
     * 顾客信息
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo('App\Models\Customer');
    }

    /**
     * 门诊病历
     * @return HasOne
     */
    public function emr(): HasOne
    {
        return $this->hasOne('App\Models\OutpatientEmr', 'reception_id');
    }

    /**
     * 门诊处方
     * @return HasMany
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany('App\Models\OutpatientPrescription', 'reception_id');
    }

    /**
     * 处方明细
     */
    public function prescriptionDetails(): Outpatient|HasManyThrough
    {
        return $this->hasManyThrough(
            'App\Models\OutpatientPrescriptionDetail',
            'App\Models\OutpatientPrescription',
            'reception_id'
        );
    }

    /**
     * 顾客操作日志
     * @return MorphMany
     */
    public function log(): MorphMany
    {
        return $this->morphMany('App\Models\CustomerLog', 'logable');
    }

    /**
     * 生命周期
     * @return MorphMany
     */
    public function cycle(): MorphMany
    {
        return $this->morphMany('App\Models\CustomerLifeCycle', 'cycle');
    }

    /**
     * 网电咨询记录
     * @return HasMany
     */
    public function reservation(): HasMany
    {
        return $this->hasMany('App\Models\Reservation', 'customer_id', 'customer_id');
    }

    /**
     * 沟通记录
     * @return MorphMany
     */
    public function talk(): MorphMany
    {
        return $this->morphMany('App\Models\CustomerTalk', 'talk');
    }

    /**
     * 收费通知
     * @return MorphMany
     */
    public function cashierable(): MorphMany
    {
        return $this->morphMany('App\Models\Cashier', 'cashierable');
    }
}
