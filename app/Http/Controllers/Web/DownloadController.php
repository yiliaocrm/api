<?php

namespace App\Http\Controllers\Web;

use App\Models\ExportTask;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Web\DownloadRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    /**
     * 下载任务中心异步导出的文件
     * @param DownloadRequest $request
     * @return StreamedResponse
     */
    public function export(DownloadRequest $request): StreamedResponse
    {
        // 导出任务
        $task = ExportTask::query()->find(
            $request->input('id')
        );

        // 下载文件
        return Storage::download($task->file_path);
    }
}
