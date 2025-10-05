<?php

namespace App\Models;

use App\Traits\HasTree;

class WebMenu extends BaseModel
{
    use HasTree;

    protected $appends = [
        'text',
        'iconCls'
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array'
        ];
    }

    /**
     * 返回text字段
     * @return mixed
     */
    public function getTextAttribute()
    {
        return $this->attributes['text'] = $this->attributes['name'];
    }

    public function getIconClsAttribute()
    {
        return $this->attributes['iconCls'] = $this->attributes['icon'];
    }
}
