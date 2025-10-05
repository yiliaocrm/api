<?php

namespace App\Models;

use App\Traits\FsidTrait;

class MaterialShare extends BaseModel
{
    use FsidTrait;

    protected $guarded = [];

    public function getFsidKey()
    {
        return 'sid';
    }
}
