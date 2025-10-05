<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends BaseModel
{
    protected $table = 'warehouse';

    protected function casts(): array
    {
        return [
            'disabled' => 'boolean',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::saving(function ($warehouse) {
            $warehouse->keyword = implode(',', parse_pinyin($warehouse->name));
        });

        // 创建完仓库,新增商品分仓预警
        static::created(function ($warehouse) {
            $time = Carbon::now()->toDateTimeString();
            $prefix = DB::getTablePrefix();
            $sql  = "insert into {$prefix}warehouse_alarm(warehouse_id,goods_id,min,max,created_at,updated_at) select {$warehouse->id},id,0,0,'{$time}','{$time}' from {$prefix}goods";
            DB::statement($sql);
        });

        static::deleted(function($warehouse) {
            $warehouse->alarms()->delete();
        });
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
     * 分仓预警
     * @return HasMany
     */
    public function alarms(): HasMany
    {
        return $this->hasMany(WarehouseAlarm::class);
    }

    /**
     * 仓库负责人
     * @return HasMany
     */
    public function warehouseUsers(): HasMany
    {
        return $this->hasMany(WarehouseUser::class);
    }
}
