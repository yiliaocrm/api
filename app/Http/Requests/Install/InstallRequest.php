<?php

namespace App\Http\Requests\Install;

use PDO;
use PDOException;
use App\Models\Admin\Admin;
use App\Exceptions\HisException;
use App\Models\Admin\AdminParameter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Http\FormRequest;

class InstallRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'start' => $this->getStartRules(),
            'install' => $this->getInstallRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'start' => $this->getStartMessages(),
            'install' => $this->getInstallMessages(),
            default => []
        };
    }

    private function getStartRules(): array
    {
        return [
            'db_host'            => 'required|string',
            'db_port'            => 'required|string',
            'db_database'        => 'required|string',
            'db_username'        => 'required|string',
            'db_password'        => 'required|string',
            'central_domain'     => 'required|string',
            'central_admin_path' => 'required|string',
            'admin_username'     => 'required|string',
            'admin_password'     => 'required|string|min:5',
        ];
    }

    private function getStartMessages(): array
    {
        return [
            'db_host.required'            => '数据库主机地址不能为空',
            'db_port.required'            => '数据库端口不能为空',
            'db_database.required'        => '数据库名称不能为空',
            'db_username.required'        => '数据库用户名不能为空',
            'db_password.required'        => '数据库密码不能为空',
            'central_domain.required'     => '后台域名不能为空',
            'central_admin_path.required' => '后台路径不能为空',
            'central_admin_path.string'   => '后台路径格式不正确',
            'admin_username.required'     => '管理员用户名不能为空',
            'admin_password.required'     => '管理员密码不能为空',
            'admin_password.min'          => '管理员密码长度不能小于5位',
        ];
    }

    private function getInstallRules(): array
    {
        return [
            'action' => [
                'required',
                'string',
                'in:' . collect($this->getInstallSteps())->pluck('key')->implode(',')
            ]
        ];
    }

    private function getInstallMessages(): array
    {
        return [
            'action.required' => '安装步骤不能为空',
            'action.string'   => '安装步骤格式不正确',
            'action.in'       => '安装步骤不合法',
        ];
    }

    /**
     * 获取系统环境要求检查结果
     * @return array
     */
    public function getEnvironmentData(): array
    {
        return [
            'php_version' => [
                'required' => '8.4.0',
                'current'  => PHP_VERSION,
                'status'   => version_compare(PHP_VERSION, '8.4.0', '>=')
            ],
            'extensions'  => [
                'bcmath'    => [
                    'required' => true,
                    'current'  => extension_loaded('bcmath'),
                    'status'   => extension_loaded('bcmath')
                ],
                'ctype'     => [
                    'required' => true,
                    'current'  => extension_loaded('ctype'),
                    'status'   => extension_loaded('ctype')
                ],
                'fileinfo'  => [
                    'required' => true,
                    'current'  => extension_loaded('fileinfo'),
                    'status'   => extension_loaded('fileinfo')
                ],
                'json'      => [
                    'required' => true,
                    'current'  => extension_loaded('json'),
                    'status'   => extension_loaded('json')
                ],
                'mbstring'  => [
                    'required' => true,
                    'current'  => extension_loaded('mbstring'),
                    'status'   => extension_loaded('mbstring')
                ],
                'openssl'   => [
                    'required' => true,
                    'current'  => extension_loaded('openssl'),
                    'status'   => extension_loaded('openssl')
                ],
                'pdo'       => [
                    'required' => true,
                    'current'  => extension_loaded('pdo'),
                    'status'   => extension_loaded('pdo')
                ],
                'tokenizer' => [
                    'required' => true,
                    'current'  => extension_loaded('tokenizer'),
                    'status'   => extension_loaded('tokenizer')
                ],
                'xml'       => [
                    'required' => true,
                    'current'  => extension_loaded('xml'),
                    'status'   => extension_loaded('xml')
                ],
                'imagick'   => [
                    'required' => true,
                    'current'  => extension_loaded('imagick'),
                    'status'   => extension_loaded('imagick')
                ],
                'xlswriter' => [
                    'required' => true,
                    'current'  => extension_loaded('xlswriter'),
                    'status'   => extension_loaded('xlswriter')
                ],
            ],
            'directories' => [
                'storage'         => [
                    'path'     => storage_path(),
                    'writable' => is_writable(storage_path()),
                    'status'   => is_writable(storage_path())
                ],
                'bootstrap/cache' => [
                    'path'     => base_path('bootstrap/cache'),
                    'writable' => is_writable(base_path('bootstrap/cache')),
                    'status'   => is_writable(base_path('bootstrap/cache'))
                ],
                '.env'            => [
                    'path'   => base_path('.env'),
                    'exists' => file_exists(base_path('.env')),
                    'status' => file_exists(base_path('.env'))
                ]
            ],
            'default_config' => $this->getDefaultConfig()
        ];
    }

    /**
     * 获取默认配置（从 .env 读取）
     * @return array
     */
    private function getDefaultConfig(): array
    {
        return [
            'db_host'            => env('DB_HOST', 'localhost'),
            'db_port'            => env('DB_PORT', '3306'),
            'db_database'        => env('DB_DATABASE', ''),
            'db_username'        => env('DB_USERNAME', 'root'),
            'db_password'        => '', // 密码不预填，安全考虑
            'central_domain'     => $this->extractDomainFromUrl(env('APP_URL', 'http://localhost')),
            'central_admin_path' => env('CENTRAL_ADMIN_PATH', 'admin'),
        ];
    }

    /**
     * 从 URL 中提取域名（不包含协议和端口）
     * @param string $url
     * @return string
     */
    private function extractDomainFromUrl(string $url): string
    {
        // 移除协议
        $url = preg_replace('#^https?://#', '', $url);
        // 移除端口
        $url = preg_replace('#:\d+$#', '', $url);
        // 移除路径
        $url = preg_replace('#/.*$#', '', $url);
        return $url;
    }


    /**
     * 验证数据库连接
     * @return void
     * @throws HisException
     */
    public function validateDatabaseConnection(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $this->input('db_host'),
            $this->input('db_port'),
            $this->input('db_database')
        );

        try {
            new PDO(
                $dsn,
                $this->input('db_username'),
                $this->input('db_password'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown database')) {
                throw new HisException('数据库不存在：' . $this->input('db_database'));
            }
            throw new HisException('数据库连接失败：' . $e->getMessage());
        }
    }

    /**
     * 保存安装配置
     * @return void
     */
    public function saveInstallConfig(): void
    {
        session([
            'install_config' => [
                'db_host'            => $this->input('db_host'),
                'db_port'            => $this->input('db_port'),
                'db_database'        => $this->input('db_database'),
                'db_username'        => $this->input('db_username'),
                'db_password'        => $this->input('db_password'),
                'central_domain'     => $this->input('central_domain'),
                'central_admin_path' => $this->input('central_admin_path'),
                'admin_username'     => $this->input('admin_username'),
                'admin_password'     => $this->input('admin_password'),
            ]
        ]);
    }

    /**
     * 获取安装步骤列表
     * @return array
     */
    public function getInstallSteps(): array
    {
        return [
            [
                'key'  => 'env',
                'name' => '更新环境配置'
            ],
            [
                'key'  => 'migration',
                'name' => '运行数据库迁移'
            ],
            [
                'key'  => 'seeder',
                'name' => '运行数据填充'
            ],
            [
                'key'  => 'admin',
                'name' => '创建管理员账户'
            ],
            [
                'key'  => 'config',
                'name' => '管理平台参数配置'
            ],
            [
                'key'  => 'complete',
                'name' => '完成安装'
            ]
        ];
    }

    /**
     * 执行安装步骤
     * @param string $step
     * @return void
     * @throws HisException
     */
    public function executeInstallStep(string $step): void
    {
        $config = session('install_config');
        if (!$config) {
            throw new HisException('安装配置信息不存在，请重新开始安装');
        }

        switch ($step) {
            case 'env':
                $this->updateEnvironmentConfig($config);
                break;

            case 'migration':
                Artisan::call('migrate:fresh', [
                    '--path'  => 'database/migrations/admin',
                    '--force' => true
                ]);
                break;

            case 'seeder':
                Artisan::call('db:seed', ['--class' => 'AdminSeeder', '--force' => true]);
                break;

            case 'config':
                $this->configAdminParameters($config);
                break;

            case 'admin':
                $this->createAdminUser($config);
                break;

            case 'complete':
                $this->completeInstallation();
        }

    }

    /**
     * 更新环境配置
     * @param array $config
     * @return void
     */
    private function updateEnvironmentConfig(array $config): void
    {
        $envPath    = base_path('.env');
        $envContent = file_get_contents($envPath);

        // 构建完整的 APP_URL（包含协议和端口）
        $appUrl = $this->buildAppUrl($config['central_domain']);

        $envContent = preg_replace(
            [
                '/DB_HOST=.*/',
                '/DB_PORT=.*/',
                '/DB_DATABASE=.*/',
                '/DB_USERNAME=.*/',
                '/DB_PASSWORD=.*/',
                '/APP_URL=.*/',
            ],
            [
                "DB_HOST={$config['db_host']}",
                "DB_PORT={$config['db_port']}",
                "DB_DATABASE={$config['db_database']}",
                "DB_USERNAME={$config['db_username']}",
                "DB_PASSWORD={$config['db_password']}",
                "APP_URL={$appUrl}",
            ],
            $envContent
        );

        file_put_contents($envPath, $envContent);
    }

    /**
     * 构建完整的应用 URL
     * @param string $domain
     * @return string
     */
    private function buildAppUrl(string $domain): string
    {
        // 如果用户填写的域名已经包含协议，直接使用
        if (str_starts_with($domain, 'http://') || str_starts_with($domain, 'https://')) {
            return rtrim($domain, '/');
        }

        // 从当前请求获取协议和端口
        $scheme = request()->getScheme(); // http 或 https
        $port = request()->getPort(); // 获取端口号

        // 构建 URL
        $url = $scheme . '://' . $domain;

        // 如果不是标准端口（80/443），添加端口号
        if (($scheme === 'http' && $port != 80) || ($scheme === 'https' && $port != 443)) {
            $url .= ':' . $port;
        }

        return $url;
    }

    /**
     * 创建管理员账户
     * @param array $config
     * @return void
     */
    private function createAdminUser(array $config): void
    {
        Admin::query()->create([
            'name'     => '管理员',
            'email'    => $config['admin_username'],
            'password' => bcrypt($config['admin_password']),
        ]);
    }

    /**
     * 配置系统参数
     * @param array $config
     * @return void
     */
    private function configAdminParameters(array $config): void
    {
        $params = [
            'reverb_host'        => $config['central_domain'],
            'central_domain'     => $config['central_domain'],
            'central_admin_path' => $config['central_admin_path'],
            'oem_system_name'    => 'HIS',
            'oem_system_logo'    => '/static/images/logo.png',
        ];
        foreach ($params as $key => $value) {
            AdminParameter::query()->updateOrCreate(
                ['name' => $key],
                ['value' => $value]
            );
        }
    }

    /**
     * 完成安装
     * @return void
     */
    private function completeInstallation(): void
    {
        // 清除安装配置
        session()->forget('install_config');

        // 写入安装锁
        if (!file_exists(storage_path('install.lock'))) {
            file_put_contents(storage_path('install.lock'), time());
        }

        // 清除缓存
        cache()->forget('admin_parameters');
    }
}
