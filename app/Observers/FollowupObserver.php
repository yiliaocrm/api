<?php

namespace App\Observers;

use App\Models\Followup;

class FollowupObserver
{
    /**
     * Handle the Followup "created" event.
     */
    public function created(Followup $followup): void
    {
        $followup->log()->create([
            'customer_id' => $followup->customer_id
        ]);
    }

    /**
     * Handle the Followup "deleted" event.
     */
    public function deleted(Followup $followup): void
    {
        $followup->log()->create([
            'customer_id' => $followup->customer_id
        ]);
        # 删除沟通记录
        $followup->talk()->delete();
    }
}
