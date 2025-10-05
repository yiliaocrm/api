<?php

namespace App\Models;


use Illuminate\Support\Facades\Storage;

class Attachment extends BaseModel
{
    protected $table = 'attachments';
    protected $guarded = [];

    public static function boot(): void
    {
        parent::boot();

        // 删除文件(注意一对多关系里面无法执行,必须使用each)
        static::deleting(function ($attachment) {
            Storage::disk($attachment->disk)->delete($attachment->file_path);
        });
    }

    /**
     * 关联模型
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function model()
    {
        return $this->morphTo();
    }


}
