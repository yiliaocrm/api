<?php

namespace App\Http\Requests\Install;

use App\Exceptions\HisException;
use App\Models\Admin\Admin;
use App\Models\Admin\AdminParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Artisan;
use PDO;
use PDOException;

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
            'db_host' => 'required|string',
            'db_port' => 'required|string',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'required|string',
            'redis_host' => 'required|string',
            'redis_port' => 'required|numeric',
            'redis_password' => 'nullable|string',
            'central_domain' => 'required|string',
            'central_admin_path' => 'required|string',
            'admin_username' => 'required|string',
            'admin_password' => 'required|string|min:5',
        ];
    }

    private function getStartMessages(): array
    {
        return [
            'db_host.required' => '数据库主机地址不能为空',
            'db_port.required' => '数据库端口不能为空',
            'db_database.required' => '数据库名称不能为空',
            'db_username.required' => '数据库用户名不能为空',
            'db_password.required' => '数据库密码不能为空',
            'redis_host.required' => 'Redis 主机地址不能为空',
            'redis_port.required' => 'Redis 端口不能为空',
            'redis_port.numeric' => 'Redis 端口必须为数字',
            'central_domain.required' => '后台域名不能为空',
            'central_admin_path.required' => '后台路径不能为空',
            'central_admin_path.string' => '后台路径格式不正确',
            'admin_username.required' => '管理员用户名不能为空',
            'admin_password.required' => '管理员密码不能为空',
            'admin_password.min' => '管理员密码长度不能小于5位',
        ];
    }

    private function getInstallRules(): array
    {
        return [
            'action' => [
                'required',
                'string',
                'in:'.collect($this->getInstallSteps())->pluck('key')->implode(','),
            ],
        ];
    }

    private function getInstallMessages(): array
    {
        return [
            'action.required' => '安装步骤不能为空',
            'action.string' => '安装步骤格式不正确',
            'action.in' => '安装步骤不合法',
        ];
    }

    /**
     * 获取系统环境要求检查结果
     */
    public function getEnvironmentData(): array
    {
        return [
            'php_version' => [
                'required' => '8.4.0',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '8.4.0', '>='),
            ],
            'extensions' => [
                'bcmath' => [
                    'required' => true,
                    'current' => extension_loaded('bcmath'),
                    'status' => extension_loaded('bcmath'),
                ],
                'ctype' => [
                    'required' => true,
                    'current' => extension_loaded('ctype'),
                    'status' => extension_loaded('ctype'),
                ],
                'fileinfo' => [
                    'required' => true,
                    'current' => extension_loaded('fileinfo'),
                    'status' => extension_loaded('fileinfo'),
                ],
                'json' => [
                    'required' => true,
                    'current' => extension_loaded('json'),
                    'status' => extension_loaded('json'),
                ],
                'mbstring' => [
                    'required' => true,
                    'current' => extension_loaded('mbstring'),
                    'status' => extension_loaded('mbstring'),
                ],
                'openssl' => [
                    'required' => true,
                    'current' => extension_loaded('openssl'),
                    'status' => extension_loaded('openssl'),
                ],
                'pdo' => [
                    'required' => true,
                    'current' => extension_loaded('pdo'),
                    'status' => extension_loaded('pdo'),
                ],
                'tokenizer' => [
                    'required' => true,
                    'current' => extension_loaded('tokenizer'),
                    'status' => extension_loaded('tokenizer'),
                ],
                'xml' => [
                    'required' => true,
                    'current' => extension_loaded('xml'),
                    'status' => extension_loaded('xml'),
                ],
                'imagick' => [
                    'required' => true,
                    'current' => extension_loaded('imagick'),
                    'status' => extension_loaded('imagick'),
                ],
                'xlswriter' => [
                    'required' => true,
                    'current' => extension_loaded('xlswriter'),
                    'status' => extension_loaded('xlswriter'),
                ],
            ],
            'directories' => [
                'storage' => [
                    'path' => storage_path(),
                    'writable' => is_writable(storage_path()),
                    'status' => is_writable(storage_path()),
                ],
                'bootstrap/cache' => [
                    'path' => base_path('bootstrap/cache'),
                    'writable' => is_writable(base_path('bootstrap/cache')),
                    'status' => is_writable(base_path('bootstrap/cache')),
                ],
                '.env' => [
                    'path' => base_path('.env'),
                    'exists' => file_exists(base_path('.env')),
                    'status' => file_exists(base_path('.env')),
                ],
            ],
        ];
    }

    /**
     * 验证数据库连接
     *
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
                throw new HisException('数据库不存在：'.$this->input('db_database'));
            }
            throw new HisException('数据库连接失败：'.$e->getMessage());
        }
    }

    /**
     * 验证 Redis 连接
     *
     * @throws HisException
     */
    public function validateRedisConnection(): void
    {
        $redis = new \Redis;

        try {
            $connected = $redis->connect(
                $this->input('redis_host'),
                (int) $this->input('redis_port'),
                3 // 连接超时 3 秒
            );

            if (! $connected) {
                throw new HisException('Redis 连接失败：无法连接到服务器');
            }

            $password = $this->input('redis_password');
            if ($password !== null && $password !== '') {
                $redis->auth($password);
            }

            $redis->ping();
        } catch (HisException $e) {
            throw $e;
        } catch (\RedisException $e) {
            throw new HisException('Redis 连接失败：'.$e->getMessage());
        } catch (\Exception $e) {
            throw new HisException('Redis 连接失败：'.$e->getMessage());
        } finally {
            try {
                $redis->close();
            } catch (\Exception) {
                // 忽略关闭时的异常
            }
        }
    }

    /**
     * 保存安装配置
     */
    public function saveInstallConfig(): void
    {
        session([
            'install_config' => [
                'db_host' => $this->input('db_host'),
                'db_port' => $this->input('db_port'),
                'db_database' => $this->input('db_database'),
                'db_username' => $this->input('db_username'),
                'db_password' => $this->input('db_password'),
                'redis_host' => $this->input('redis_host'),
                'redis_port' => $this->input('redis_port'),
                'redis_password' => $this->input('redis_password'),
                'central_domain' => $this->input('central_domain'),
                'central_admin_path' => $this->input('central_admin_path'),
                'admin_username' => $this->input('admin_username'),
                'admin_password' => $this->input('admin_password'),
            ],
        ]);
    }

    /**
     * 获取安装步骤列表
     */
    public function getInstallSteps(): array
    {
        return [
            [
                'key' => 'env',
                'name' => '更新环境配置',
            ],
            [
                'key' => 'migration',
                'name' => '运行数据库迁移',
            ],
            [
                'key' => 'seeder',
                'name' => '运行数据填充',
            ],
            [
                'key' => 'admin',
                'name' => '创建管理员账户',
            ],
            [
                'key' => 'config',
                'name' => '管理平台参数配置',
            ],
            [
                'key' => 'complete',
                'name' => '完成安装',
            ],
        ];
    }

    /**
     * 执行安装步骤
     *
     * @throws HisException
     */
    public function executeInstallStep(string $step): void
    {
        $config = session('install_config');
        if (! $config) {
            throw new HisException('安装配置信息不存在，请重新开始安装');
        }

        switch ($step) {
            case 'env':
                $this->updateEnvironmentConfig($config);
                break;

            case 'migration':
                Artisan::call('migrate:fresh', [
                    '--path' => 'database/migrations/admin',
                    '--force' => true,
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
     */
    private function updateEnvironmentConfig(array $config): void
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        $redisPassword = ($config['redis_password'] === null || $config['redis_password'] === '')
            ? 'null'
            : $config['redis_password'];

        $envContent = preg_replace(
            [
                '/DB_HOST=.*/',
                '/DB_PORT=.*/',
                '/DB_DATABASE=.*/',
                '/DB_USERNAME=.*/',
                '/DB_PASSWORD=.*/',
                '/APP_URL=.*/',
                '/REDIS_HOST=.*/',
                '/REDIS_PORT=.*/',
                '/REDIS_PASSWORD=.*/',
            ],
            [
                "DB_HOST={$config['db_host']}",
                "DB_PORT={$config['db_port']}",
                "DB_DATABASE={$config['db_database']}",
                "DB_USERNAME={$config['db_username']}",
                "DB_PASSWORD={$config['db_password']}",
                "APP_URL={$config['central_domain']}",
                "REDIS_HOST={$config['redis_host']}",
                "REDIS_PORT={$config['redis_port']}",
                "REDIS_PASSWORD={$redisPassword}",
            ],
            $envContent
        );

        file_put_contents($envPath, $envContent);
    }

    /**
     * 创建管理员账户
     */
    private function createAdminUser(array $config): void
    {
        Admin::query()->create([
            'name' => '管理员',
            'email' => $config['admin_username'],
            'password' => bcrypt($config['admin_password']),
        ]);
    }

    /**
     * 配置系统参数
     */
    private function configAdminParameters(array $config): void
    {
        $params = [
            'reverb_host' => $config['central_domain'],
            'central_domain' => $config['central_domain'],
            'central_admin_path' => $config['central_admin_path'],
            'oem_system_name' => 'HIS',
            'oem_system_logo' => '/static/images/logo.png',
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
     */
    private function completeInstallation(): void
    {
        // 清除安装配置
        session()->forget('install_config');

        // 写入安装锁
        if (! file_exists(storage_path('install.lock'))) {
            file_put_contents(storage_path('install.lock'), time());
        }

        // 清除缓存
        cache()->forget('admin_parameters');
    }

    /**
     * 获取安装默认配置（直接解析 .env 文件）
     */
    public function getConfigData(): array
    {
        $fields = [
            'db_host' => 'DB_HOST',
            'db_port' => 'DB_PORT',
            'db_database' => 'DB_DATABASE',
            'db_username' => 'DB_USERNAME',
            'db_password' => 'DB_PASSWORD',
            'redis_host' => 'REDIS_HOST',
            'redis_port' => 'REDIS_PORT',
            'redis_password' => 'REDIS_PASSWORD',
        ];

        if (! file_exists(base_path('.env'))) {
            return array_fill_keys(array_keys($fields), '');
        }

        $content = file_get_contents(base_path('.env'));
        $get = function (string $key) use ($content): string {
            if (preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $content, $matches)) {
                $value = trim($matches[1]);

                return ($value === '' || $value === 'null') ? '' : $value;
            }

            return '';
        };

        return array_combine(
            array_keys($fields),
            array_map($get, array_values($fields))
        );
    }
}
