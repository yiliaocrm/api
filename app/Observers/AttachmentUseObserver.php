<?php

namespace App\Observers;

use App\Models\Attachment;
use App\Models\AttachmentUse;

class AttachmentUseObserver
{
    /**
     * 创建附件引用时触发
     * 自动增加附件的引用计数
     *
     * @param AttachmentUse $attachmentUse
     * @return void
     */
    public function created(AttachmentUse $attachmentUse): void
    {
        Attachment::query()
            ->where('id', $attachmentUse->attachment_id)
            ->increment('reference_count');
    }

    /**
     * 删除附件引用时触发
     * 自动减少附件的引用计数
     *
     * @param AttachmentUse $attachmentUse
     * @return void
     */
    public function deleted(AttachmentUse $attachmentUse): void
    {
        Attachment::query()
            ->where('id', $attachmentUse->attachment_id)
            ->where('reference_count', '>', 0)
            ->decrement('reference_count');
    }
}
