<?php

namespace App\Contracts\Documents;

use App\Data\ExtractionResult;

interface DocumentExtractor
{
    public function supports(string $extension): bool;

    public function extract(string $filePath, string $originalName, string $extension): ExtractionResult;
}
