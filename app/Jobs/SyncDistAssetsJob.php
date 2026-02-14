<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SyncDistAssetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 0;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $stats = $this->syncDistAssets();

        Log::info('dist静态资源同步完成', $stats);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('dist静态资源同步失败', [
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * 同步public/dist目录文件到独立S3存储
     *
     * @return array{total:int,uploaded:int,failed:int,errors:array<int, string>}
     */
    protected function syncDistAssets(): array
    {
        $basePath = public_path('dist');
        if (! is_dir($basePath)) {
            throw new RuntimeException('本地目录 public/dist 不存在');
        }

        $files = File::allFiles($basePath);
        $disk = Storage::build($this->buildDiskConfig());

        $stats = [
            'total' => count($files),
            'uploaded' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($files as $file) {
            $sourcePath = $file->getPathname();
            $relative = str_replace('\\', '/', ltrim(str_replace($basePath, '', $sourcePath), DIRECTORY_SEPARATOR));
            $targetPath = 'dist/'.$relative;

            $stream = null;
            try {
                $stream = fopen($sourcePath, 'r');
                if ($stream === false) {
                    throw new RuntimeException("无法读取文件: {$sourcePath}");
                }

                $disk->put($targetPath, $stream, ['visibility' => 'public']);
                $stats['uploaded']++;
            } catch (Throwable $e) {
                $stats['failed']++;
                $stats['errors'][] = "{$targetPath}: {$e->getMessage()}";
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }

        return $stats;
    }

    /**
     * 构建静态资源独立S3配置
     */
    protected function buildDiskConfig(): array
    {
        return [
            'driver' => 's3',
            'key' => admin_parameter('dist_sync_s3_access_key_id'),
            'secret' => admin_parameter('dist_sync_s3_secret_access_key'),
            'region' => admin_parameter('dist_sync_s3_region'),
            'bucket' => admin_parameter('dist_sync_s3_bucket'),
            'endpoint' => admin_parameter('dist_sync_s3_endpoint') ?: null,
            'use_path_style_endpoint' => (bool) admin_parameter('dist_sync_s3_use_path_style_endpoint'),
            'url' => admin_parameter('dist_sync_s3_url') ?: null,
            'visibility' => 'public',
            'throw' => true,
        ];
    }
}
