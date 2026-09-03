<?php

namespace App\Services\Import\Contracts;

use App\Models\Import;
use App\Services\Import\Dto\CreateImportDto;

interface ImportServiceInterface
{
    public function createImport(CreateImportDto $dto): Import;

    public function processImport(int $importId): void;
}
