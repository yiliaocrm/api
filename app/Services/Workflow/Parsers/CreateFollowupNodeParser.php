<?php

namespace App\Services\Workflow\Parsers;

use App\Services\Workflow\Contracts\NodeParserInterface;

class CreateFollowupNodeParser implements NodeParserInterface
{
    /**
     * 解析回访任务节点
     *
     * @param  array  $node  前端节点数据
     * @return array 节点配置
     */
    public function parse(array $node): array
    {
        $parameters = $node['parameters'] ?? [];

        return [
            'type' => 'create_followup',
            'name' => $node['nodeName'] ?? '回访任务',
            'configuration' => $parameters,
        ];
    }

    /**
     * 验证节点参数是否有效
     *
     * @param  array  $node  前端节点数据
     */
    public function validate(array $node): bool
    {
        $parameters = $node['parameters'] ?? [];

        // 验证必填字段
        if (empty($parameters['title'])) {
            return false;
        }

        if (empty($parameters['type'])) {
            return false;
        }

        $followupUserMode = $parameters['followup_user_mode'] ?? 'specified';

        if ($followupUserMode === 'specified') {
            if (empty($parameters['followup_user'])) {
                return false;
            }
        } elseif ($followupUserMode === 'relation') {
            $allowedRelations = ['ascription', 'consultant', 'service_id', 'doctor_id'];
            if (empty($parameters['followup_user_relation']) || ! in_array($parameters['followup_user_relation'], $allowedRelations)) {
                return false;
            }
        } else {
            return false;
        }

        if (! empty($parameters['followup_user_fallback']) && empty($parameters['followup_user_fallback_user'])) {
            return false;
        }

        // 验证日期配置
        $dateMode = $parameters['date_mode'] ?? 'relative';

        if ($dateMode === 'relative') {
            if (! isset($parameters['date_offset']) || $parameters['date_offset'] < 1) {
                return false;
            }
            if (! in_array($parameters['date_unit'] ?? '', ['hours', 'days', 'weeks'])) {
                return false;
            }
        } elseif ($dateMode === 'absolute') {
            if (empty($parameters['absolute_date'])) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * 获取该解析器支持的节点类型
     */
    public function getNodeType(): string
    {
        return 'create_followup';
    }
}
