<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;

class ParseCdpField
{
    /**
     * 筛选条件
     * @var Builder
     */
    protected Builder $filterQuery;
    /**
     * 排除条件
     * @var Builder
     */
    protected Builder $excludeQuery;

    /**
     * 存储所有字段配置
     * @var Collection
     */
    protected Collection $fieldsConfig;

    public function __construct()
    {
        $this->filterQuery  = DB::table('customer')->distinct()->select(['customer.id AS customer_id']);
        $this->excludeQuery = $this->filterQuery->clone();
        $this->fieldsConfig = DB::table('customer_group_fields')->get()->keyBy(function ($item) {
            return $item->table . '.' . $item->field;
        });
    }

    /**
     * 筛选条件(为空查询所有客户)
     * @param array|null $conditions
     * @return $this
     */
    public function filter(?array $conditions): self
    {
        if ($conditions) {
            $this->buildQuery($conditions, $this->filterQuery, $this->filterQuery, 'and');
        }
        return $this;
    }

    /**
     * 排除条件(为空不排除)
     * @param array|null $conditions
     * @return $this
     */
    public function exclude(?array $conditions): self
    {
        if ($conditions) {
            $this->buildQuery($conditions, $this->excludeQuery, $this->excludeQuery, 'or');
        }
        return $this;
    }

    /**
     * 生成查询条件
     * @param array $conditions
     * @param Builder $rootQuery
     * @param Builder $query
     * @param string $logical
     * @return void
     */
    public function buildQuery(array $conditions, Builder $rootQuery, Builder $query, string $logical): void
    {
        if ($conditions['type'] === 'group') {
            $this->handleGroup($conditions, $rootQuery, $query, $logical);
        } else {
            $this->handleField($conditions, $rootQuery, $query, $logical);
        }
    }

    /**
     * 处理分组
     * @param array $group
     * @param Builder $rootQuery
     * @param Builder $query
     * @param string $logical
     * @return void
     */

    protected function handleGroup(array $group, Builder $rootQuery, Builder $query, string $logical): void
    {
        $children     = $group['children'];
        $groupLogical = $group['logical'];

        $query->where(function (Builder $q) use ($children, $groupLogical, $rootQuery) {
            foreach ($children as $child) {
                $this->buildQuery($child, $rootQuery, $q, $groupLogical);
            }
        }, null, null, $logical);
    }

    /**
     * 处理字段
     * @param array $conditions
     * @param Builder $rootQuery
     * @param Builder $query
     * @param string $logical
     * @return void
     */
    protected function handleField(array $conditions, Builder $rootQuery, Builder $query, string $logical): void
    {
        $table       = $conditions['table'];
        $field       = $conditions['field'];
        $column      = $table . '.' . $field;
        $value       = $conditions['value'];
        $boolean     = $logical === 'and' ? 'and' : 'or';
        $operator    = $conditions['operator'];
        $fieldConfig = $this->fieldsConfig[$table . '.' . $field];

        // 解析时间字段
        if ($this->isDateField($fieldConfig->field_type)) {
            $value = $this->resolveDateValue($value);
        }

        // 自动连表
        if ($fieldConfig->auto_join) {
            $this->autoJoinTable($table, $rootQuery);
        }

        // 需要特殊处理的字段
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

        // 处理日期字段查询
        if ($this->isDateField($fieldConfig->field_type)) {
            $this->handleDateQuery($query, $column, $operator, $value, $boolean, $fieldConfig->field_type);
            return;
        }

        // 普通where条件
        $this->handleStandardQuery($query, $column, $operator, $value, $boolean);
    }

    /**
     * 处理标准查询
     * @param Builder $query
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @param string $boolean
     * @return void
     */
    protected function handleStandardQuery(Builder $query, string $column, string $operator, mixed $value, string $boolean): void
    {
        switch ($operator) {
            case 'in':
                $query->whereIn($column, $value, $boolean);
                break;
            case 'not in':
                $query->whereNotIn($column, $value, $boolean);
                break;
            case 'between':
                $query->whereBetween($column, $value, $boolean);
                break;
            case 'not between':
                $query->whereNotBetween($column, $value, $boolean);
                break;
            case 'like':
                $query->where($column, 'like', '%' . $value . '%', $boolean);
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
     * 处理日期查询
     * @param Builder $query
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
     * @param string $field_type
     * @return void
     */
    protected function handleDateQuery(Builder $query, string $column, mixed $operator, mixed $value, string $boolean, string $field_type): void
    {
        // date类型直接比较
        if ($field_type === 'date') {
            $this->handleStandardQuery($query, $column, $operator, $value, $boolean);
            return;
        }

        // 表前缀
        $prefix = DB::connection()->getTablePrefix();

        // datetime和timestamp需要使用whereDate
        switch ($operator) {
            case '=':
            case '<=':
            case '>=':
            case '<':
            case '>':
            case '!=':
                $query->whereDate($column, $operator, $value, $boolean);
                break;
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereBetween(DB::raw('DATE(' . $prefix . $column . ')'), $value, $boolean);
                }
                break;
            case 'not between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereNotBetween(DB::raw('DATE(' . $prefix . $column . ')'), $value, $boolean);
                }
                break;
            case 'is null':
                $query->whereNull($column, $boolean);
                break;
            case 'is not null':
                $query->whereNotNull($column, $boolean);
                break;
            default:
                // 对于其他操作符，使用标准查询
                $this->handleStandardQuery($query, $column, $operator, $value, $boolean);
                break;
        }
    }

    /**
     * 判断是否为日期字段
     * @param string $field_type
     * @return bool
     */
    protected function isDateField(string $field_type): bool
    {
        return in_array($field_type, ['date', 'datetime', 'timestamp']);
    }

    /**
     * 解析日期值
     * @param mixed $value
     * @return mixed
     */
    protected function resolveDateValue(mixed $value): mixed
    {
        // 处理单个值
        if (is_array($value) && isset($value['type']) && $value['type'] === 'dynamic') {
            return $this->calculateDynamicDate($value['offset']);
        }
        // 处理数组值（用于 between 等操作）
        if (is_array($value) && !isset($value['type'])) {
            return array_map(function ($item) {
                if (is_array($item) && isset($item['type']) && $item['type'] === 'dynamic') {
                    return $this->calculateDynamicDate($item['offset']);
                }
                return $item;
            }, $value);
        }
        return $value;
    }

    /**
     * 计算动态日期
     * @param int $offset
     * @return string
     */
    protected function calculateDynamicDate(int $offset): string
    {
        return Carbon::now()->addDays($offset)->format('Y-m-d');
    }

    /**
     * 处理自定义查询配置
     * @param array $configs
     * @param Builder $rootQuery
     * @param Builder $query
     * @param string $operator
     * @param mixed $value
     * @param string $logical
     * @return void
     */
    protected function handleQueryConfig(array $configs, Builder $rootQuery, Builder $query, string $operator, mixed $value, string $logical): void
    {
        $config = collect($configs)->firstWhere('operator', $operator);
        if (!$config) {
            return;
        }

        // 处理连表
        $joins = $config['joins'] ?? [];
        // 检查是否已经存在这个表的 JOIN
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
     * 处理自定义查询配置中where条件
     * @param array $clause
     * @param Builder $query
     * @param mixed $value
     * @param string $logical
     * @return void
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
//                $query->where($clause['column'], $clause['operator'], $clause['value'] ?? $value, $logical);
                $this->handleStandardQuery($query, $clause['column'], $clause['operator'], $clause['value'] ?? $value, $logical);
                break;
        }
    }

    /**
     * 处理bindings
     * @param array $bindings
     * @param mixed $value
     * @return array
     */
    protected function handleBindings(array $bindings, mixed $value): array
    {
        return array_map(function ($binding) use ($value) {
            // 检查是否是获取最后一个元素的特定格式占位符
            if ($binding === '{$value[-1]}') {
                return is_array($value) ? end($value) : $value;
            }

            // 检查是否是获取指定索引元素的特定格式占位符
            if (preg_match('/\{\$value\[(\d+)]}/', $binding, $matches)) {
                $index = (int)$matches[1];
                if (is_array($value) && isset($value[$index])) {
                    return $value[$index];
                }
                return null;  // 当value不是数组或索引不存在时返回null
            }

            // 检查是否是简单的 {$value} 占位符，直接返回$value
            if ($binding === '{$value}') {
                return $value;
            }

            // 如果不是特定格式的占位符，或者索引不存在，保持原样
            return $binding;
        }, $bindings);
    }


    /**
     * 根据表名添加关联
     * @param string $table
     * @param Builder $query
     * @return void
     */
    protected function autoJoinTable(string $table, Builder $query): void
    {
        // customer 表不需要关联
        if ($table === 'customer') {
            return;
        }

        // 检查是否已经存在这个表的 JOIN
        $joins = collect($query->joins)->pluck('table');
        if ($joins->contains($table)) {
            return;
        }

        $query->leftJoin($table, 'customer.id', '=', $table . '.customer_id');
    }

    public function getSql(): string
    {
        // 筛选sql
        $filterSql = $this->getFullSql($this->filterQuery);
        if (!$this->excludeQuery->wheres) {
            return $filterSql;
        }

        // 排除sql
        $excludeSql = $this->getFullSql($this->excludeQuery);
        return "SELECT * FROM ($filterSql) AS filtered WHERE filtered.customer_id NOT IN ($excludeSql)";
    }

    /**
     * 生成完整的 SQL 语句
     * @param Builder $query
     * @return string
     */
    protected function getFullSql(Builder $query): string
    {
        $sql      = $query->toSql();
        $bindings = $query->getBindings();
        foreach ($bindings as $binding) {
            $sql = preg_replace('/\?/', "'{$binding}'", $sql, 1);
        }
        return $sql;
    }
}
