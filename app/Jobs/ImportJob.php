<?php

namespace App\Jobs;

use App\Imports\BaseImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\ImportTemplate;
use App\Models\ImportHistory;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ImportJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ImportTemplate $importTemplate,
        protected ImportHistory $importHistory
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

        $useImport->import($this->importHistory->id);
    }

    /**
     * @return mixed
     */
    public function unique(): mixed
    {
        return $this->importHistory->id;
    }
}
