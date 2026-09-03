<?php

namespace App\Jobs;

use App\Services\Import\Contracts\ImportServiceInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessImportJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $importId,
    ) {
    }

    public function uniqueId(): string
    {
        return (string) $this->importId;
    }

    public function handle(ImportServiceInterface $importService): void
    {
        $importService->processImport($this->importId);
    }
}
