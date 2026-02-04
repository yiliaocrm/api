<?php

namespace App\Models;

class WorkflowConfig extends BaseModel
{
    /**
     * 关联到工作流
     */
    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * 获取触发配置
     */
    public function getTriggerConfig()
    {
        return $this->where('config_type', 'trigger')->first();
    }

    /**
     * 获取调度配置
     */
    public function getScheduleConfig()
    {
        return $this->where('config_type', 'schedule')->first();
    }
}
