<?php

namespace App\Jobs;

use App\Models\ImportTask;
use App\Imports\BaseImport;
use App\Models\ImportTemplate;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ImportJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ImportTemplate $importTemplate,
        protected ImportTask     $importTask
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var BaseImport $useImport */
        $useImport = $this->importTemplate->use_import;

        $useImport->import($this->importTask->id);
    }

    /**
     * @return mixed
     */
    public function unique(): mixed
    {
        return $this->importTask->id;
    }
}
