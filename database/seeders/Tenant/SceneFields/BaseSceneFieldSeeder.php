<?php

namespace Database\Seeders\Tenant\SceneFields;

abstract class BaseSceneFieldSeeder
{
    /**
     * 转换设置配置为选项
     */
    protected function convertSettingConfigToOptions(string $key): array
    {
        $config  = config($key);
        $options = [];
        foreach ($config as $value => $label) {
            $options[] = ['label' => $label, 'value' => $value];
        }
        return $options;
    }

    /**
     * 获取配置数据
     */
    abstract public function getConfig(): array;
}
