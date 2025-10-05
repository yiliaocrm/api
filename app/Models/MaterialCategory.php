<?php

namespace App\Models;

use App\Traits\FsidTrait;

class MaterialCategory extends BaseModel
{
    use FsidTrait;

    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($model) {
            $model->ranking = $model->id;
            $model->save();
        });
    }

    public function getFsidKey(): string
    {
        return 'cid';
    }
}
