<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

/**
 * 解析筛选条件
 */
class ParseFilter
{
    /**
     * 应用筛选条件到查询构造器
     *
     * @param Builder $query
     * @param array $filters
     * @param string $page
     * @return Builder
     */
    public static function applyFilters(Builder $query, array $filters, string $page): Builder
    {
        $fields = DB::table('scene_fields')->where('page', $page)->get();

        // 如果没有配置场景化搜索条件或者没有查询字段，则直接返回
        if ($fields->isEmpty()) {
            return $query;
        }

        foreach ($filters as $filter) {
            $field       = $filter['field'];
            $value       = $filter['value'] ?? null;
            $operator    = $filter['operator'];
            $fieldConfig = $fields->where('field', $field)->first();
            $column      = "{$fieldConfig->table}.{$field}";

            // 字段不在配置中，直接跳过
            if (!$fields->contains('field', $field)) {
                continue;
            }

            // 需要特殊处理的字段
            if ($fieldConfig->query_config) {
                self::handleQueryConfig(
                    json_decode($fieldConfig->query_config, true),
                    $query,
                    $operator,
                    $value
                );
                continue;
            }

            // 处理日期字段查询
            if (self::isDateField($fieldConfig->field_type)) {
                self::handleDateQuery($query, $column, $operator, $value, $fieldConfig->field_type);
                continue;
            }

            // 处理标准查询
            self::handleStandardQuery($query, $column, $operator, $value);
        }

        return $query;
    }

    /**
     * 判断是否为日期字段
     * @param string $field_type
     * @return bool
     */
    protected static function isDateField(string $field_type): bool
    {
        return in_array($field_type, ['date', 'datetime', 'timestamp']);
    }

    /**
     * 处理标准查询
     * @param Builder $query
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return void
     */
    protected static function handleStandardQuery(Builder $query, string $column, string $operator, mixed $value): void
    {
        switch ($operator) {
            case 'in':
                $query->whereIn($column, $value);
                break;
            case 'not in':
                $query->whereNotIn($column, $value);
                break;
            case 'between':
                $query->whereBetween($column, $value);
                break;
            case 'not between':
                $query->whereNotBetween($column, $value);
                break;
            case 'like':
                $query->where($column, 'like', '%' . $value . '%');
                break;
            case 'is null':
                $query->whereNull($column);
                break;
            case 'is not null':
                $query->whereNotNull($column);
                break;
            default:
                $query->where($column, $operator, $value);
                break;
        }
    }

    /**
     * 处理日期查询
     * @param Builder $query
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $field_type
     * @return void
     */
    protected static function handleDateQuery(Builder $query, string $column, mixed $operator, mixed $value, string $field_type): void
    {
        // date类型直接比较
        if ($field_type === 'date') {
            self::handleStandardQuery($query, $column, $operator, $value);
            return;
        }

        // 表前缀
        $prefix = DB::getTablePrefix();

        // datetime和timestamp需要使用whereDate
        switch ($operator) {
            case '=':
            case '<=':
            case '>=':
            case '<':
            case '>':
            case '!=':
                $query->whereDate($column, $operator, $value);
                break;
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereBetween(DB::raw('DATE(' . $prefix . $column . ')'), $value);
                }
                break;
            case 'not between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereNotBetween(DB::raw('DATE(' . $prefix . $column . ')'), $value);
                }
                break;
            case 'is null':
                $query->whereNull($column);
                break;
            case 'is not null':
                $query->whereNotNull($column);
                break;
            default:
                // 对于其他操作符，使用标准查询
                self::handleStandardQuery($query, $column, $operator, $value);
                break;
        }
    }

    /**
     * 处理自定义查询配置
     * @param array $configs
     * @param Builder $query
     * @param string $operator
     * @param mixed $value
     * @return void
     */
    protected static function handleQueryConfig(array $configs, Builder $query, string $operator, mixed $value): void
    {
        $config = collect($configs)->firstWhere('operator', $operator);
        if (!$config) {
            return;
        }

        // 处理连表
        $joins = $config['joins'] ?? [];

        foreach ($joins as $join) {
            $query->join($join['table'], $join['first'], $join['operator'], $join['second'], $join['type']);
        }

        // 处理where条件
        $wheres = $config['wheres'] ?? [];
        foreach ($wheres as $where) {
            self::handleQueryConfigWhereClause($where, $query, $value);
        }
    }

    /**
     * 处理自定义查询配置中where条件
     * @param array $clause
     * @param Builder $query
     * @param mixed $value
     * @return void
     */
    protected static function handleQueryConfigWhereClause(array $clause, Builder $query, mixed $value): void
    {
        switch ($clause['type']) {
            case 'whereRaw':
                $bindings = self::handleBindings($clause['bindings'] ?? [], $value);
                $query->whereRaw($clause['sql'], $bindings);
                break;
            case 'whereNull':
                $query->whereNull($clause['column']);
                break;
            case 'whereNotNull':
                $query->whereNotNull($clause['column']);
                break;
            default:
                self::handleStandardQuery($query, $clause['column'], $clause['operator'], $clause['value'] ?? $value);
                break;
        }
    }

    /**
     * 处理bindings
     * @param array $bindings
     * @param mixed $value
     * @return array
     */
    protected static function handleBindings(array $bindings, mixed $value): array
    {
        return array_map(function ($binding) use ($value) {
            // 首先处理占位符
            return preg_replace_callback('/\{(\$value(?:\[-?\d+])?)}/', function ($matches) use ($value) {
                $placeholder = $matches[1];

                // 处理数组索引访问
                if (preg_match('/\$value\[(-?\d+)]/', $placeholder, $indexMatches)) {
                    $index = (int)$indexMatches[1];
                    if ($index < 0) {
                        // 处理负数索引
                        return is_array($value) ? array_values($value)[count($value) + $index] : $value;
                    }
                    return is_array($value) && isset($value[$index]) ? $value[$index] : $value;
                }

                // 简单的 $value 替换
                return is_array($value) ? $value[0] : $value;
            }, $binding);
        }, $bindings);
    }
}
