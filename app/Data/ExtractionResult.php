<?php

namespace App\Data;

/**
 * Normalized extraction output produced by Docling or the native TXT reader.
 */
final readonly class ExtractionResult
{
    /**
     * @param  array<int, array<string, mixed>>  $pages
     * @param  array<int, array<string, mixed>>  $headings
     * @param  array<int, array<string, mixed>>  $tables
     * @param  array<int, string>  $warnings
     * @param  array<string, mixed>  $quality
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $text,
        public array $pages = [],
        public array $headings = [],
        public array $tables = [],
        public array $warnings = [],
        public array $quality = [],
        public ?int $pageCount = null,
        public bool $ocrUsed = false,
        public string $engine = 'docling',
        public string $engineStatus = 'success',
        public array $metadata = [],
    ) {}
}
