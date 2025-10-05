<?php

namespace App\Traits;

use App\Helpers\ParseFilter;
use Illuminate\Database\Eloquent\Builder;

/**
 * 解析场景化搜索条件
 */
trait QueryConditionsTrait
{
    /**
     * 解析场景化搜索条件
     *
     * @param Builder $query 查询构造器
     * @param string $page 适配页面
     * @param array $filters 搜索条件(用于异步队列导出传入)
     * @return Builder
     */
    public function scopeQueryConditions(Builder $query, string $page, array $filters = []): Builder
    {
        $filters = $filters ?: request()->input('filters', []);
        if (empty($filters)) {
            return $query;
        }
        return ParseFilter::applyFilters($query, $filters, $page);
    }
}
