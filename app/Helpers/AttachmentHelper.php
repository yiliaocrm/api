<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class AttachmentHelper
{
    /**
     * 上传文件
     * @param UploadedFile $file
     * @param string $path
     * @param string $disk
     * @return array
     */
    public function upload(UploadedFile $file, string $path = 'other', string $disk = ''): array
    {
        if (!$disk) {
            $disk = config('filesystems.default');
        }

        // 如果是本地驱动，增加租户路径前缀
        if (config("filesystems.disks.{$disk}.driver") !== 'local') {
            $path = sprintf(
                '%s/%s',
                config('tenancy.filesystem.suffix_base') . tenant()->getTenantKey(),
                $path
            );
        }

        $path = sprintf('%s/%s', $path, date('Y/m/d'));
        $md5  = md5_file($file->getRealPath());
        $path = Storage::disk($disk)->put($path, $file);

        return [
            'disk'      => $disk,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'file_ext'  => $file->getClientOriginalExtension(),
            'file_mime' => $file->getClientMimeType(),
            'file_md5'  => $md5,
            'is_image'  => $this->isImage($file->getClientMimeType()),
            'user_id'   => user() ? user()->id : null,
            'ip'        => request()->getClientIp(),
        ];
    }

    /**
     * 生成图片缩略图
     * @param UploadedFile $file
     * @param string $path
     * @param int $width
     * @param int $height
     * @param string $disk
     * @return array
     */
    public function makeImageThumb(UploadedFile $file, string $path = 'other', int $width = 140, int $height = 140, string $disk = ''): array
    {
        if (!$disk) {
            $disk = config('filesystems.default');
        }

        // 如果是本地驱动，增加租户路径前缀
        if (config("filesystems.disks.{$disk}.driver") !== 'local') {
            $path = sprintf(
                '%s/%s',
                config('tenancy.filesystem.suffix_base') . tenant()->getTenantKey(),
                $path
            );
        }

        $path = sprintf(
            '%s/%s/%s_thumb.%s',
            $path,
            date('Y/m/d'),
            Str::random(40),
            $file->getClientOriginalExtension()
        );

        $image = Image::read($file)->resize($width, $height)->encodeByExtension('jpg', 80);

        Storage::disk($disk)->put($path, $image);

        return [
            'disk'      => $disk,
            'file_path' => $path,
            'file_size' => strlen($image),
            'file_ext'  => 'jpg',
            'width'     => $width,
            'height'    => $height,
        ];
    }

    /**
     * 判断是否为图片
     * @param string $mime
     * @return bool
     */
    private function isImage(string $mime): bool
    {
        return in_array($mime, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/bmp',
            'image/webp',
            'image/svg+xml',
        ]);
    }
}
