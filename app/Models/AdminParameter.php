<?php

namespace App\Models;

class AdminParameter extends BaseModel
{
    protected $keyType = 'string';
    protected $primaryKey = 'name';

    /**
     * 获取value属性时进行类型转换
     *
     */
    public function getValueAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($value) ? (str_contains($value, '.') ? (float)$value : (int)$value) : $value,
            'array' => json_decode($value, true) ?? [],
            'object' => json_decode($value) ?? (object)[],
            default => $value,
        };
    }

    /**
     * 设置value属性时进行类型转换
     * @param $value
     * @return void
     */
    public function setValueAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['value'] = null;
            return;
        }

        $this->attributes['value'] = match ($this->type) {
            'boolean' => $value ? 'true' : 'false',
            'array' => is_array($value) ? json_encode($value) : $value,
            'object' => is_object($value) ? json_encode($value) : $value,
            default => (string)$value,
        };
    }
}
