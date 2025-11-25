<?php
namespace App\Services;

use App\Imports\BaseImport;
use App\Jobs\ImportJob;
use App\Models\ImportHistory;
use App\Models\ImportTemplate;

class ImportService
{
    public function import($historyId)
    {
        //
        $history = ImportHistory::query()->where('id', $historyId)->first();

        $template = ImportTemplate::query()->where('id', $history->template_id)->first();

        // 是否使用异步导入，如果导入历史的成功行数（1000） > async_limit （500） 限制
        if ($history->success_rows > $template->async_limit) {
            ImportJob::dispatch($template, $history);
        } else {
            $template->use_import->import($historyId);
        }
    }

    public function prepare(ImportTemplate $template, $file)
    {
        if (! $template->use_import instanceof BaseImport) {
            throw new \Exception('导入类不符合要求，必须继承 App\Imports\BaseImport 基类');
        }

        return $template->use_import->setTemplateId($template->id)->prepare($file);
    }
}
