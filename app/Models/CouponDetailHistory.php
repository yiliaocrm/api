<?php

namespace App\Models;


class CouponDetailHistory extends BaseModel
{
    protected $guarded = [];

    /**
     * 历史记录关联
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function historyable()
    {
        return $this->morphTo();
    }
}
