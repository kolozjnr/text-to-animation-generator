<?php

namespace App\Services\Extraction;

use App\Contracts\Documents\DocumentExtractor;
use App\Data\ExtractionResult;
use App\Exceptions\DocumentExtractionException;

class PlainTextDocumentExtractor implements DocumentExtractor
{
    public function supports(string $extension): bool
    {
        return strtolower($extension) === 'txt';
    }

    public function extract(string $filePath, string $originalName, string $extension): ExtractionResult
    {
        $contents = file_get_contents($filePath);

        if ($contents === false) {
            throw new DocumentExtractionException('The text file could not be read.');
        }

        $contents = $this->decode($contents);
        $contents = str_replace(["\r\n", "\r"], "\n", $contents);
        $contents = trim($contents);

        $headings = [];

        if (preg_match_all('/^(#{1,6})\s+(.+)$/m', $contents, $matches, PREG_SET_ORDER) !== false) {
            foreach ($matches as $match) {
                $headings[] = [
                    'text' => trim($match[2]),
                    'level' => strlen($match[1]),
                    'page_number' => 1,
                ];
            }
        }

        $characterCount = mb_strlen($contents);
        $warnings = $characterCount === 0
            ? ['No text was extracted from the document.']
            : [];

        return new ExtractionResult(
            text: $contents,
            pages: [[
                'page_number' => 1,
                'headings' => $headings,
                'tables' => [],
            ]],
            headings: $headings,
            tables: [],
            warnings: $warnings,
            quality: [
                'character_count' => $characterCount,
                'page_count' => 1,
                'pages_with_text' => $characterCount > 0 ? 1 : 0,
                'empty_pages' => $characterCount > 0 ? [] : [1],
                'heading_count' => count($headings),
                'table_count' => 0,
            ],
            pageCount: 1,
            ocrUsed: false,
            engine: 'native-text',
            engineStatus: 'success',
            metadata: ['original_name' => basename($originalName)],
        );
    }

    private function decode(string $contents): string
    {
        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            return substr($contents, 3);
        }

        if (str_starts_with($contents, "\xFF\xFE")) {
            $converted = mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16LE');

            if (! is_string($converted)) {
                throw new DocumentExtractionException('The text file encoding is not supported.');
            }

            return $converted;
        }

        if (str_starts_with($contents, "\xFE\xFF")) {
            $converted = mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16BE');

            if (! is_string($converted)) {
                throw new DocumentExtractionException('The text file encoding is not supported.');
            }

            return $converted;
        }

        if (! mb_check_encoding($contents, 'UTF-8')) {
            throw new DocumentExtractionException('The text file encoding is not supported.');
        }

        return $contents;
    }
}
