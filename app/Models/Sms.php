<?php

namespace App\Models;

use App\Enums\SmsStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sms extends BaseModel
{
    /**
     * 数据类型转换
     * @var array
     */
    protected $casts = [
        'status'           => SmsStatus::class,
        'sent_at'          => 'datetime',
        'gateway_response' => 'array',
    ];

    /**
     * 获取状态文本
     * @return Attribute
     */
    protected function statusText(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status->getLabel(),
        );
    }

    /**
     * 关联短信模板
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class, 'template_id');
    }

    /**
     * 关联发送用户
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
