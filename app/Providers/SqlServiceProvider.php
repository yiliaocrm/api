<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Events\QueryExecuted;

class SqlServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 系统未完成初始化安装 或者 日志未开启
        if (!file_exists(storage_path('install.lock')) || !$this->app['config']->get('logging.query.enabled', false)) {
            return;
        }

        $trigger = $this->app['config']->get('logging.query.trigger');

        if (!empty($trigger) && !$this->requestHasTrigger($trigger)) {
            return;
        }

        $this->app['events']->listen(QueryExecuted::class, function (QueryExecuted $query) {
            if (
                $query->time < $this->app['config']->get('logging.query.slower_than', 0)
                || str($query->sql)->is($this->app['config']->get('logging.query.except', []))
            ) {
                return;
            }

            $sqlWithPlaceholders = str_replace(['%', '?', '%s%s'], ['%%', '%s', '?'], $query->sql);

            $bindings = $query->connection->prepareBindings($query->bindings);
            $pdo      = $query->connection->getPdo();
            $realSql  = $sqlWithPlaceholders;
            $duration = $this->formatDuration($query->time / 1000);

            if (count($bindings) > 0) {
                $realSql = vsprintf($sqlWithPlaceholders, array_map(
                    static fn($binding) => $binding === null ? 'NULL' : $pdo->quote($binding),
                    $bindings
                ));
            }
            Log::channel(config('logging.query.channel', config('logging.default')))
                ->debug(sprintf('[%s] [%s] %s | %s: %s', $query->connection->getDatabaseName(), $duration, $realSql,
                    request()->method(), request()->getRequestUri()));
        });
    }

    /**
     * 检查请求中是否包含触发器
     * @param string $trigger
     * @return bool
     */
    public function requestHasTrigger(string $trigger): bool
    {
        return false !== getenv($trigger) || \request()->hasHeader($trigger) || \request()->has($trigger) || \request()->hasCookie($trigger);
    }

    /**
     * 格式化时间
     * @param float $seconds
     * @return string
     */
    private function formatDuration(float $seconds): string
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000) . 'μs';
        } elseif ($seconds < 1) {
            return round($seconds * 1000, 2) . 'ms';
        }

        return round($seconds, 2) . 's';
    }
}
