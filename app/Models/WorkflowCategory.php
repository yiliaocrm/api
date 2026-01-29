<?php

namespace App\Models;

class WorkflowCategory extends BaseModel
{
    /**
     * 模型启动方法
     */
    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($model) {
            $model->sort = $model->id;
            $model->save();
        });
    }
}
