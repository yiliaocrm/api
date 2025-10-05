<?php

namespace App\Upgrades\Versions;

use App\Models\CustomerGroup;

class Version102 extends BaseVersion
{
    /**
     * 版本号
     * @return string
     */
    public function version(): string
    {
        return '1.0.2';
    }


    /**
     * 升级方法
     * @return void
     */
    public function upgrade(): void
    {
        info('执行1.0.2升级脚本');

        // 查找分群数据
        $groups = CustomerGroup::query()
            ->select(['id', 'filter_rule', 'exclude_rule'])
            ->where('type', 'dynamic')
            ->whereRaw('filter_rule LIKE ?', ['%"table":"customer","field":"customer_group_id","value"%'])
            ->orWhereRaw('exclude_rule LIKE ?', ['%"table":"customer","field":"customer_group_id","value"%'])
            ->get();

        foreach ($groups as $group) {
            // 处理 filter_rule
            if (!empty($group->filter_rule)) {
                $group->filter_rule = $this->processRule($group->filter_rule);
            }

            // 处理 exclude_rule
            if (!empty($group->exclude_rule)) {
                $group->exclude_rule = $this->processRule($group->exclude_rule);
            }

            $group->save();
        }
    }

    /**
     * 递归处理规则
     * @param array $rule
     * @return array
     */
    private function processRule(array $rule): array
    {
        if ($rule['type'] === 'field') {
            if ($rule['table'] === 'customer' && $rule['field'] === 'customer_group_id') {
                $categoryId = CustomerGroup::query()
                    ->where('id', $rule['value'])
                    ->value('category_id');

                $rule['value'] = [$categoryId, $rule['value']];
            }
            return $rule;
        }

        if ($rule['type'] === 'group' && isset($rule['children'])) {
            $rule['children'] = array_map([$this, 'processRule'], $rule['children']);
        }

        return $rule;
    }
}
