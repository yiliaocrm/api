<?php

namespace App\Models;

use DB;
use Ramsey\Uuid\Uuid;


class Distributor extends BaseModel
{
    protected $table = 'distributor';
    protected $keyType = 'string';
    public $incrementing = false;


    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = Uuid::uuid7()->toString();
        });

        # 更新节点信息
        static::created(function ($model) {
            if ($model->parentid) {
                $parent = static::find($model->parentid);
                static::find($model->id)->update([
                    'tree' => $parent->tree . '-' . $model->id
                ]);
                $parent->update(['child' => 1]);
            } else {
                static::find($model->id)->update(['tree' => '0-' . $model->id]);
            }
        });

        static::updating(function ($model) {
            $dirty = $model->getDirty();

            # 移动到其他节点
            if (isset($dirty['parentid']) && $dirty['parentid']) {
                $parent      = static::find($model->parentid);
                $model->tree = $parent->tree . '-' . $model->id;
                $parent->update(['child' => 1]);
            }

            # 移动到顶级
            if (isset($dirty['parentid']) && $dirty['parentid'] == 0) {
                $model->tree = '0-' . $model->id;
            }
        });

        static::updated(function ($model) {
            $dirty    = $model->getDirty();
            $original = $model->getRawOriginal();

            # 移动节点
            if (isset($dirty['parentid'])) {
                DB::select(DB::raw("update " . DB::getTablePrefix() . "distributor set tree = CONCAT('{$model->tree}-',id) where tree like '{$original['tree']}-%'"));
                $child = static::where('parentid', $original['parentid'])->count();
                if (!$child) {
                    static::find($original['parentid'])->update(['child' => 0]);
                }
            }
        });

        static::deleted(function ($model) {
            static::where('tree', 'like', "{$model->tree}-%")->delete();

            # 更新父级
            if ($model->parentid != 0) {
                $parent = static::find($model->parentid);

                if ($parent->getAllChild()->count() > 1) {
                    $parent->update(['child' => 1]);
                } else {
                    $parent->update(['child' => 0]);
                }
            }
        });
    }

    # 获取所有子节点
    public function getAllChild()
    {
        return static::where('tree', 'like', "{$this->tree}-%")->orWhere('id', $this->id)->orderBy('id', 'ASC')->get();
    }
}
