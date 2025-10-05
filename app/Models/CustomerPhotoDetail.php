<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;

class CustomerPhotoDetail extends BaseModel
{
    protected $guarded = [];

    /**
     * 返回完整url
     * @param $url
     * @return string|void
     */
    public function getThumbAttribute($url)
    {
        if (!empty($url)) {
            return get_attachment_url($url);
        }
    }

    /**
     * 返回完整url
     * @param $url
     * @return string|void
     */
    public function getFilePathAttribute($url)
    {
        if (!empty($url)) {
            return get_attachment_url($url);
        }
    }

    /**
     * 顾客日志
     * @return MorphMany
     */
    public function customerLog(): MorphMany
    {
        return $this->morphMany(CustomerLog::class, 'logable');
    }
}
