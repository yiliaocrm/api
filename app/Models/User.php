<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Cartalyst\Sentinel\Users\EloquentUser;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends EloquentUser
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'password',
        'name',
        'remark',
        'permissions',
        'department_id',
        'scheduleable',
        'secret',
        'last_login',
        'extension',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password'
    ];

    protected function casts(): array
    {
        return [
            'permissions'  => 'json',
            'banned'       => 'boolean',
            'scheduleable' => 'boolean',
        ];
    }

    /**
     * 用户的所有权限
     * @var array
     */
    protected array $mergedPermissions = [];

    public static function boot(): void
    {
        parent::boot();

        static::saving(function ($user) {
            $user->keyword = implode(',', array_merge([$user->email], parse_pinyin($user->name)));
        });
    }

    public function setPermissionsAttribute(array $permissions): void
    {
        $this->attributes['permissions'] = $permissions ?
            json_encode(array_map(function ($key) {
                return boolval($key);
            }, $permissions))
            : '';
    }

    /**
     * 登录日志
     * @return HasMany
     */
    public function loginLog(): HasMany
    {
        return $this->hasMany(UsersLogin::class);
    }

    /**
     * 排班记录
     * @return HasMany
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * 部门
     * @return HasOne
     */
    public function department(): HasOne
    {
        return $this->hasOne(Department::class, 'id', 'department_id');
    }

    /**
     * 岗位（多对多关系）
     * @return BelongsToMany
     */
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'user_positions', 'user_id', 'position_id')
            ->withTimestamps();
    }

    /**
     * 是否超级管理员
     * @return mixed
     */
    public function isSuperUser()
    {
        return $this->hasAccess('superuser');
    }

    /**
     * 返回用户的所有权限
     * @return array
     */
    public function getMergedPermissions(): array
    {
        if (!$this->mergedPermissions) {
            $permissions = [];
            $roles       = $this->roles;

            foreach ($roles as $role) {
                $permissions = array_merge($permissions, $role->permissions);
            }
            $this->mergedPermissions = array_merge($permissions, $this->getPermissions());
        }
        return $this->mergedPermissions;
    }

    /**
     * 禁止登录
     * @return void
     */
    public function ban(): void
    {
        if (!$this->banned) {
            $this->banned = 1;
            $this->save();
        }
    }

    /**
     * 允许登录
     * @return void
     */
    public function unban(): void
    {
        if ($this->banned) {
            $this->banned = 0;
            $this->save();
        }
    }

    public static function getInfo($id)
    {
        if (!$id) {
            return false;
        }

        static $_info = [];

        if (!isset($_info[$id])) {
            $_info[$id] = static::find($id);
        }

        return $_info[$id];
    }

    /**
     * 为数组 / JSON 序列化准备日期
     * @param DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    /*===============================用户模块查询权限========================*/

    /**
     * [网电咨询]-[操作员]查询权限
     * @return array
     */
    public function getReservationViewUsersPermission(): array
    {
        return collect($this->getMergedPermissions())->filter()->filter(function ($value, $key) {
            return (str_contains($key, 'reservation.view.user'));
        })
            ->keys()
            ->map(function ($key) {
                return str_replace('reservation.view.user', '', $key);
            })
            ->push($this->id)
            ->unique()
            ->toArray();
    }

    /**
     * 获取[分诊接待]可查询权限用户
     * @return array
     */
    public function getReceptionViewUsersPermission(): array
    {
        return collect($this->getMergedPermissions())->filter()->filter(function ($value, $key) {
            return (str_contains($key, 'reception.view.user'));
        })
            ->keys()
            ->map(function ($key) {
                return str_replace('reception.view.user', '', $key);
            })
            ->push($this->id)
            ->unique()
            ->toArray();
    }

    /**
     * 获取[现场咨询]可查询用户权限
     * @return array
     */
    public function getConsultantViewUsersPermission(): array
    {
        return collect($this->getMergedPermissions())->filter()->filter(function ($value, $key) {
            return (str_contains($key, 'consultant.view.user'));
        })
            ->keys()
            ->map(function ($key) {
                return str_replace('consultant.view.user', '', $key);
            })
            ->push($this->id)
            ->unique()
            ->toArray();
    }

    /**
     * 获取[顾客管理]可查询用户权限
     * @return array
     */

    public function getCustomerViewUsersPermission(): array
    {
        return collect($this->getMergedPermissions())
            ->filter()  // 会自动去除 false 的权限
            ->flatMap(function ($value, $key) {
                // 查看指定员工顾客
                if (Str::startsWith($key, 'customer.view.user.id')) {
                    return [(int)Str::after($key, 'customer.view.user.id.')];
                }
                // 查看同部门员工顾客
                if (Str::startsWith($key, 'customer.view.self.department')) {
                    return User::query()->where('department_id', $this->department_id)->pluck('id')->toArray();
                }
                // 查看指定部门员工顾客
                if (Str::startsWith($key, 'customer.view.department.id')) {
                    return User::query()->where('department_id', (int)Str::after($key, 'customer.view.department.id.'))->pluck('id')->toArray();
                }
                return [];
            })
            ->push($this->id)
            ->unique()
            ->values()
            ->all();
    }


    /**
     * 获取[回访记录]可查询用户权限
     * @return array
     */
    public function getFollowupViewUsersPermission(): array
    {
        return collect($this->getMergedPermissions())->filter()->filter(function ($value, $key) {
            return (str_contains($key, 'followup.view.user'));
        })
            ->keys()
            ->map(function ($key) {
                return str_replace('followup.view.user', '', $key);
            })
            ->push($this->id)
            ->unique()
            ->toArray();
    }

    /**
     * 获取[治疗划扣]查看权限
     * @return array
     */
    public function getTreatmentViewDepartmentsPermission(): array
    {
        return collect($this->getMergedPermissions())
            ->filter()
            ->flatMap(function ($value, $key) {
                // 查看指定部门
                if (Str::startsWith($key, 'treatment.view.department.id')) {
                    return [(int)Str::after($key, 'treatment.view.department.id.')];
                }
                return [];
            })
            // 查看归属部门
            ->push($this->department_id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * 可以查看[收费列表]的用户
     * @return array
     */
    public function getUserIdsForCashier(): array
    {
        return collect($this->getMergedPermissions())
            ->filter()
            ->flatMap(function ($value, $key) {
                // 查看归属部门
                if (Str::startsWith($key, 'cashier.view.self.department')) {
                    return User::query()->where('department_id', $this->department_id)->pluck('id')->toArray();
                }
                // 查看指定部门
                if (Str::startsWith($key, 'cashier.view.department.id')) {
                    return User::query()->where('department_id', (int)Str::after($key, 'cashier.view.department.id.'))->pluck('id')->toArray();
                }
                // 查看指定人员
                if (Str::startsWith($key, 'cashier.view.user.id')) {
                    return [(int)Str::after($key, 'cashier.view.user.id.')];
                }
                return [];
            })
            ->push($this->id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * 可以查看[职工工作明细表]的用户
     * @return array
     */
    public function getUserIdsForSalesPerformance(): array
    {
        return collect($this->getMergedPermissions())
            ->filter()
            ->flatMap(function ($value, $key) {
                // 查看归属部门
                if (Str::startsWith($key, 'sales_performance.view.self.department')) {
                    return User::query()->where('department_id', $this->department_id)->pluck('id')->toArray();
                }
                // 查看指定部门
                if (Str::startsWith($key, 'sales_performance.view.department.id')) {
                    return User::query()->where('department_id', (int)Str::after($key, 'sales_performance.view.department.id.'))->pluck('id')->toArray();
                }
                // 查看指定人员
                if (Str::startsWith($key, 'sales_performance.view.user.id')) {
                    return [(int)Str::after($key, 'sales_performance.view.user.id.')];
                }
                return [];
            })
            ->push($this->id)
            ->unique()
            ->values()
            ->all();
    }
}
