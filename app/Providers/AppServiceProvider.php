<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 系统未完成初始化安装
        if (!file_exists(storage_path('install.lock'))) {
            return;
        }

        // 获取所有后台参数配置
        $parameters = admin_parameter();

        // 定义参数名到配置项的映射
        $configMap = [
            'sql_log_enabled'             => 'logging.query.enabled',
            'sql_log_slower_than'         => 'logging.query.slower_than',
            'central_admin_path'          => 'tenancy.admin_path',
            'file_system_disk'            => 'filesystems.default',
            'aws_access_key_id'           => 'filesystems.disks.s3.key',
            'aws_secret_access_key'       => 'filesystems.disks.s3.secret',
            'aws_default_region'          => 'filesystems.disks.s3.region',
            'aws_bucket'                  => 'filesystems.disks.s3.bucket',
            'aws_url'                     => 'filesystems.disks.s3.url',
            'aws_endpoint'                => 'filesystems.disks.s3.endpoint',
            'aws_signed_url'              => 'filesystems.disks.s3.signed_url',
            'aws_use_path_style_endpoint' => 'filesystems.disks.s3.use_path_style_endpoint',
        ];

        // 遍历映射，设置配置
        foreach ($configMap as $paramName => $configKey) {
            if (isset($parameters[$paramName])) {
                config([$configKey => $parameters[$paramName]]);
            }
        }

        // 特殊处理需要数组的配置
        if (isset($parameters['central_domain'])) {
            config(['tenancy.central_domains' => [$parameters['central_domain']]]);
        }

        // 特殊处理需要设置多个值的配置
        if (isset($parameters['reverb_host'])) {
            config(['reverb.servers.reverb.hostname' => $parameters['reverb_host']]);
            config(['reverb.apps.apps.0.options.host' => $parameters['reverb_host']]);
            config(['broadcasting.connections.reverb.options.host' => $parameters['reverb_host']]);
        }
        if (isset($parameters['reverb_port'])) {
            config(['reverb.apps.apps.0.options.port' => $parameters['reverb_port']]);
            config(['broadcasting.connections.reverb.options.port' => $parameters['reverb_port']]);
        }
        if (isset($parameters['reverb_scheme'])) {
            config(['reverb.apps.apps.0.options.scheme' => $parameters['reverb_scheme']]);
            config(['reverb.apps.apps.0.options.useTLS' => $parameters['reverb_scheme'] === 'https']);
            config(['broadcasting.connections.reverb.options.scheme' => $parameters['reverb_scheme']]);
            config(['broadcasting.connections.reverb.options.useTLS' => $parameters['reverb_scheme'] === 'https']);
        }
        if (isset($parameters['reverb_app_id'])) {
            config([
                'reverb.apps.apps.0.app_id'              => $parameters['reverb_app_id'],
                'broadcasting.connections.reverb.app_id' => $parameters['reverb_app_id']
            ]);
        }
        if (isset($parameters['reverb_app_key'])) {
            config([
                'reverb.apps.apps.0.key'              => $parameters['reverb_app_key'],
                'broadcasting.connections.reverb.key' => $parameters['reverb_app_key']
            ]);
        }
        if (isset($parameters['reverb_app_secret'])) {
            config([
                'reverb.apps.apps.0.secret'              => $parameters['reverb_app_secret'],
                'broadcasting.connections.reverb.secret' => $parameters['reverb_app_secret']
            ]);
        }
    }
}
