<?php

namespace App\Helpers;

use App\Services\Workflow\ContextResolver;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ParseWorkflowConditionField
{
    protected Collection $fieldsConfig;

    protected ContextResolver $contextResolver;

    public function __construct()
    {
        $this->fieldsConfig = DB::table('workflow_condition_fields')->get()->keyBy(function ($item) {
            return $item->table.'.'.$item->field;
        });
        $this->contextResolver = new ContextResolver;
    }

    /**
     * 评估一个条件组（对应一个分支）
     *
     * @param  array  $group  条件组配置 {matchType, rules}
     * @param  array  $context  工作流上下文
     * @param  array  $nameMap  节点名称到ID的映射
     * @return bool 是否命中
     */
    public function evaluateGroup(array $group, array $context, array $nameMap = []): bool
    {
        $rules = $group['rules'] ?? [];
        if (empty($rules)) {
            return false;
        }

        $matchType = strtolower($group['matchType'] ?? 'all');

        // 按 table 分组规则
        $rulesByTable = [];
        foreach ($rules as $rule) {
            $table = $rule['table'] ?? '';
            if ($table === '') {
                continue;
            }
            $rulesByTable[$table][] = $rule;
        }

        if (empty($rulesByTable)) {
            return false;
        }

        // 对每个表构建查询
        $ruleBoolean = $matchType === 'any' ? 'or' : 'and';
        $tableResults = [];
        foreach ($rulesByTable as $baseTable => $tableRules) {
            $query = DB::table($baseTable)->select(DB::raw('1'));

            foreach ($tableRules as $rule) {
                $this->applyRuleToQuery($query, $query, $rule, $context, $nameMap, $ruleBoolean);
            }

            $tableResults[] = $query->limit(1)->exists();
        }

        // 根据 matchType 计算最终结果
        if ($matchType === 'any') {
            return in_array(true, $tableResults, true);
        }

        // all
        return ! in_array(false, $tableResults, true);
    }

    /**
     * 将单条规则应用到查询上
     */
    protected function applyRuleToQuery(Builder $rootQuery, Builder $query, array $rule, array $context, array $nameMap, string $boolean = 'and'): void
    {
        $table = $rule['table'] ?? '';
        $field = $rule['field'] ?? '';
        $operator = $rule['operator'] ?? '=';
        $value = $this->resolveRuleValue($rule, $context, $nameMap);
        $column = $table.'.'.$field;
        $key = $table.'.'.$field;

        $fieldConfig = $this->fieldsConfig[$key] ?? null;
        if (! $fieldConfig) {
            return;
        }

        // 处理 query_config 特殊查询
        if ($fieldConfig->query_config) {
            $this->handleQueryConfig(
                json_decode($fieldConfig->query_config, true),
                $rootQuery,
                $query,
                $operator,
                $value,
                $boolean
            );

            return;
        }

        // 标准查询
        $this->handleStandardQuery($query, $column, $operator, $value, $boolean);
    }

    /**
     * 解析规则值（支持上下文变量引用）
     *
     * 自动检测值是否包含 {{ }} 模板表达式来决定是否解析上下文变量。
     */
    protected function resolveRuleValue(array $rule, array $context, array $nameMap): mixed
    {
        $value = $rule['value'] ?? null;

        if (! is_string($value) || $value === '') {
            return $value;
        }

        // 值包含 {{ }} 模板表达式则解析上下文变量
        if (preg_match('/\{\{[\s\S]*?\}\}/', $value)) {
            // 如果整个值就是一个 {{ }} 表达式，保持原始类型（数字等）
            if (preg_match('/^\{\{\s*(.+?)\s*\}\}$/', $value)) {
                $path = trim(preg_replace('/^\{\{\s*|\s*\}\}$/', '', $value));
                $raw = $this->contextResolver->resolve($path, $context, $nameMap);
                if ($raw !== null) {
                    return $raw;
                }
            }

            return $this->contextResolver->renderTemplate($value, $context, $nameMap);
        }

        return $value;
    }

    /**
     * 处理标准查询（复用 ParseCdpField 逻辑）
     */
    protected function handleStandardQuery(Builder $query, string $column, string $operator, mixed $value, string $boolean): void
    {
        switch ($operator) {
            case 'in':
                $query->whereIn($column, (array) $value, $boolean);
                break;
            case 'not in':
                $query->whereNotIn($column, (array) $value, $boolean);
                break;
            case 'between':
                $query->whereBetween($column, (array) $value, $boolean);
                break;
            case 'not between':
                $query->whereNotBetween($column, (array) $value, $boolean);
                break;
            case 'like':
                $query->where($column, 'like', '%'.$value.'%', $boolean);
                break;
            case 'is null':
                $query->whereNull($column, $boolean);
                break;
            case 'is not null':
                $query->whereNotNull($column, $boolean);
                break;
            default:
                $query->where($column, $operator, $value, $boolean);
                break;
        }
    }

    /**
     * 处理自定义查询配置（复用 ParseCdpField 逻辑）
     */
    protected function handleQueryConfig(array $configs, Builder $rootQuery, Builder $query, string $operator, mixed $value, string $logical): void
    {
        $config = collect($configs)->firstWhere('operator', $operator);
        if (! $config) {
            return;
        }

        // 处理连表
        $joins = $config['joins'] ?? [];
        $existingJoins = collect($rootQuery->joins)->pluck('table');

        foreach ($joins as $join) {
            if ($existingJoins->contains($join['table'])) {
                continue;
            }
            $rootQuery->join($join['table'], $join['first'], $join['operator'], $join['second'], $join['type']);
        }

        // 处理where条件
        $wheres = $config['wheres'] ?? [];
        foreach ($wheres as $where) {
            $this->handleQueryConfigWhereClause($where, $query, $value, $logical);
        }
    }

    /**
     * 处理自定义查询配置中的where条件
     */
    protected function handleQueryConfigWhereClause(array $clause, Builder $query, mixed $value, string $logical): void
    {
        switch ($clause['type']) {
            case 'whereRaw':
                $bindings = $this->handleBindings($clause['bindings'] ?? [], $value);
                $query->whereRaw($clause['sql'], $bindings, $logical);
                break;
            case 'whereNull':
                $query->whereNull($clause['column'], $logical);
                break;
            case 'whereNotNull':
                $query->whereNotNull($clause['column'], $logical);
                break;
            default:
                $this->handleStandardQuery($query, $clause['column'], $clause['operator'], $clause['value'] ?? $value, $logical);
                break;
        }
    }

    /**
     * 处理bindings占位符
     */
    protected function handleBindings(array $bindings, mixed $value): array
    {
        return array_map(function ($binding) use ($value) {
            if ($binding === '{$value[-1]}') {
                return is_array($value) ? end($value) : $value;
            }

            if (preg_match('/\{\$value\[(\d+)]}/', $binding, $matches)) {
                $index = (int) $matches[1];
                if (is_array($value) && isset($value[$index])) {
                    return $value[$index];
                }

                return null;
            }

            if ($binding === '{$value}') {
                return $value;
            }

            return $binding;
        }, $bindings);
    }
}
