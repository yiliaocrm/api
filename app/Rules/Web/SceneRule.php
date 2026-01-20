<?php

namespace App\Rules\Web;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Validation\Rule;

class SceneRule implements Rule
{
    /**
     * @var Collection 配置字段
     */
    protected Collection $fields;

    /**
     * @var string 提醒消息
     */
    protected string $message = '场景化搜索验证失败!';

    public function __construct(string $page)
    {
        $this->fields = DB::table('scene_fields')->where('page', $page)->get();
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (empty($value)) {
            return true;
        }

        if ($this->fields->isEmpty()) {
            $this->message = '没有配置场景化搜索条件';
            return false;
        }

        foreach ($value as $filter) {
            // 优先通过 field_alias 匹配，其次使用 field 匹配（与 ParseFilter::applyFilters 保持一致）
            $exists = $this->fields->contains(function ($item) use ($filter) {
                return $item->field_alias === $filter['field'] || $item->field === $filter['field'];
            });

            if (!$exists) {
                $this->message = $filter['field'] . '字段不在配置中';
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }
}
