<?php

namespace App\Models;

use Ip;
use Agent;
use App\Enums\UserLoginType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsersLogin extends BaseModel
{
    protected $table = 'users_login';

    protected $casts = [
        'type' => UserLoginType::class,
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($log) {
            $info = Ip::find(request()->ip());

            $log->ip       = request()->ip();
            $log->country  = $info[0];
            $log->province = $info[1];
            $log->city     = $info[2];
            $log->browser  = Agent::browser();
            $log->platform = Agent::platform();
        });
    }

    /**
     * 员工信息
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取类型文本
     * @return string
     */
    public function getTypeTextAttribute(): string
    {
        return $this->type->getLabel();
    }
}
