<?php

namespace Database\Seeders\Admin;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Models\Admin\AdminParameter;

class AdminParameterSeeder extends Seeder
{
    /**
     * 后台参数配置填充
     * @return void
     */
    public function run(): void
    {
        $parameters = [
            [
                'name'   => 'central_domain',
                'value'  => '',
                'type'   => 'string',
                'remark' => '控制中心域名',
            ],
            [
                'name'   => 'central_admin_path',
                'value'  => 'admin',
                'type'   => 'string',
                'remark' => '控制中心后台路由前缀',
            ],
            [
                'name'   => 'central_login_2fa',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '控制中心登录是否启用双重验证',
            ],
            [
                'name'   => 'google2fa',
                'value'  => '',
                'type'   => 'string',
                'remark' => '谷歌双重验证密钥',
            ],
            [
                'name'   => 'dist_path',
                'value'  => '/dist/',
                'type'   => 'string',
                'remark' => '静态资源地址',
            ],
            [
                'name'   => 'reverb_app_id',
                'value'  => random_int(100000, 999999),
                'type'   => 'string',
                'remark' => 'Reverb应用ID',
            ],
            [
                'name'   => 'reverb_app_key',
                'value'  => Str::random(20),
                'type'   => 'string',
                'remark' => 'Reverb应用标识',
            ],
            [
                'name'   => 'reverb_app_secret',
                'value'  => Str::random(40),
                'type'   => 'string',
                'remark' => 'Reverb应用密钥',
            ],
            [
                'name'   => 'reverb_host',
                'value'  => '',
                'type'   => 'string',
                'remark' => '前端websocket地址',
            ],
            [
                'name'   => 'reverb_port',
                'value'  => 443,
                'type'   => 'number',
                'remark' => '前端websocket端口',
            ],
            [
                'name'   => 'reverb_scheme',
                'value'  => 'https',
                'type'   => 'string',
                'remark' => '前端websocket协议',
            ],
            [
                'name'   => 'file_system_disk',
                'value'  => 'public',
                'type'   => 'string',
                'remark' => '文件存储磁盘',
            ],
            [
                'name'   => 'aws_access_key_id',
                'value'  => '',
                'type'   => 'string',
                'remark' => 'AWS访问密钥ID',
            ],
            [
                'name'   => 'aws_secret_access_key',
                'value'  => '',
                'type'   => 'string',
                'remark' => 'AWS秘密访问密钥',
            ],
            [
                'name'   => 'aws_default_region',
                'value'  => '',
                'type'   => 'string',
                'remark' => 'AWS默认区域',
            ],
            [
                'name'   => 'aws_bucket',
                'value'  => '',
                'type'   => 'string',
                'remark' => 'AWS存储桶名称',
            ],
            [
                'name'   => 'aws_url',
                'value'  => '',
                'type'   => 'string',
                'remark' => 'AWS存储桶URL',
            ],
            [
                'name'   => 'aws_endpoint',
                'value'  => '',
                'type'   => 'string',
                'remark' => 'AWS端点',
            ],
            [
                'name'   => 'aws_use_path_style_endpoint',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '是否使用路径样式端点',
            ],
            [
                'name'   => 'aws_signed_url',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '如果 bucket 为私有访问请打开此项',
            ],
            [
                'name'   => 'his_version',
                'value'  => '1.0.0',
                'type'   => 'string',
                'remark' => '系统版本号',
            ],
            [
                'name'   => 'sql_log_enabled',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '是否记录sql查询日志',
            ],
            [
                'name'   => 'sql_log_slower_than',
                'value'  => 0,
                'type'   => 'number',
                'remark' => 'sql查询日志记录阈值(毫秒)',
            ],
            [
                'name'   => 'oem_system_name',
                'value'  => '',
                'type'   => 'string',
                'remark' => '系统名称',
            ],
            [
                'name'   => 'oem_system_logo',
                'value'  => '',
                'type'   => 'string',
                'remark' => '系统logo',
            ],
            [
                'name'   => 'oem_help_url',
                'value'  => '',
                'type'   => 'string',
                'remark' => '帮助中心地址',
            ],
            [
                'name'   => 'oem_app_qrcode',
                'value'  => '',
                'type'   => 'string',
                'remark' => 'app二维码地址',
            ],
            [
                'name'   => 'oem_service_qrcode',
                'value'  => '',
                'type'   => 'string',
                'remark' => '客服二维码地址',
            ],
            [
                'name'   => 'oem_service_phone',
                'value'  => '',
                'type'   => 'string',
                'remark' => '客服热线',
            ],
            [
                'name'   => 'oem_service_description',
                'value'  => '',
                'type'   => 'string',
                'remark' => '客服描述',
            ],
        ];
        // 添加或更新参数
        foreach ($parameters as $parameter) {
            AdminParameter::query()->firstOrCreate(['name' => $parameter['name']], $parameter);
        }
        // 删除多余的参数
        AdminParameter::query()->whereNotIn('name', array_column($parameters, 'name'))->delete();
    }
}
