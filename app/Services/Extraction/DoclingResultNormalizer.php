<?php

namespace App\Services\Extraction;

class DoclingResultNormalizer
{
    /**
     * Build a compact, private extraction artifact from Docling's JSON output.
     *
     * The reading-order text is supplied separately by Docling's text export so
     * this class only needs to recover page, heading, and table references.
     *
     * @param  array<string, mixed>|null  $json
     * @param  array<string, mixed>  $result
     */
    public function normalize(?array $json, string $text): array
    {
        $json ??= [];
        $headings = [];
        $tables = [];
        $pages = [];
        $pageTextLengths = [];
        $pageCount = 0;

        foreach ((array) ($json['pages'] ?? []) as $key => $page) {
            if (! is_array($page)) {
                continue;
            }

            $pageNumber = (int) ($page['page_no'] ?? $key);

            if ($pageNumber <= 0) {
                continue;
            }

            $pages[$pageNumber] = [
                'page_number' => $pageNumber,
                'headings' => [],
                'tables' => [],
            ];
            $pageTextLengths[$pageNumber] = 0;
            $pageCount = max($pageCount, $pageNumber);
        }

        foreach ((array) ($json['texts'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $pageNumber = $this->pageNumber($item);
            $itemText = trim((string) ($item['text'] ?? ''));
            $itemLength = mb_strlen($itemText);

            if ($pageNumber !== null) {
                $pages[$pageNumber] ??= [
                    'page_number' => $pageNumber,
                    'headings' => [],
                    'tables' => [],
                ];
                $pageTextLengths[$pageNumber] = ($pageTextLengths[$pageNumber] ?? 0) + $itemLength;
                $pageCount = max($pageCount, $pageNumber);
            }

            $label = (string) ($item['label'] ?? '');

            if (! $this->isHeading($label) || $itemText === '') {
                continue;
            }

            $heading = [
                'text' => $itemText,
                'level' => $this->headingLevel($item, $label),
                'page_number' => $pageNumber,
            ];
            $headings[] = $heading;

            if ($pageNumber !== null) {
                $pages[$pageNumber]['headings'][] = $heading;
            }
        }

        foreach ((array) ($json['tables'] ?? []) as $index => $table) {
            if (! is_array($table)) {
                continue;
            }

            $pageNumber = $this->pageNumber($table);
            $tableData = $table['data'] ?? [];
            $normalizedTable = [
                'index' => (int) $index,
                'page_number' => $pageNumber,
                'rows' => (int) ($tableData['num_rows'] ?? 0),
                'columns' => (int) ($tableData['num_cols'] ?? 0),
                'cells' => $this->tableCells($tableData),
            ];
            $tables[] = $normalizedTable;

            if ($pageNumber !== null) {
                $pages[$pageNumber] ??= [
                    'page_number' => $pageNumber,
                    'headings' => [],
                    'tables' => [],
                ];
                $pages[$pageNumber]['tables'][] = [
                    'index' => $normalizedTable['index'],
                    'rows' => $normalizedTable['rows'],
                    'columns' => $normalizedTable['columns'],
                ];
                $pageCount = max($pageCount, $pageNumber);
            }
        }

        ksort($pages);
        ksort($pageTextLengths);

        $emptyPages = [];

        foreach ($pageTextLengths as $pageNumber => $length) {
            if ($length === 0) {
                $emptyPages[] = $pageNumber;
            }
        }

        $warnings = [];

        if ($pageCount === 0) {
            $warnings[] = 'The extraction service did not return any page references.';
        }

        if ($emptyPages !== []) {
            $warnings[] = 'Some pages did not contain extractable text.';
        }

        if (trim($text) === '') {
            $warnings[] = 'No text was extracted from the document.';
        }

        return [
            'pages' => array_values($pages),
            'headings' => $headings,
            'tables' => $tables,
            'warnings' => $warnings,
            'quality' => [
                'character_count' => mb_strlen($text),
                'page_count' => $pageCount,
                'pages_with_text' => count(array_filter($pageTextLengths, static fn (int $length): bool => $length > 0)),
                'empty_pages' => $emptyPages,
                'heading_count' => count($headings),
                'table_count' => count($tables),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function pageNumber(array $item): ?int
    {
        $provenance = $item['prov'] ?? null;

        if (is_array($provenance)) {
            if (isset($provenance[0]) && is_array($provenance[0]) && isset($provenance[0]['page_no'])) {
                return (int) $provenance[0]['page_no'];
            }

            if (isset($provenance['page_no'])) {
                return (int) $provenance['page_no'];
            }
        }

        return null;
    }

    private function isHeading(string $label): bool
    {
        return in_array($label, ['title', 'section_header'], true);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function headingLevel(array $item, string $label): int
    {
        if (isset($item['level']) && is_numeric($item['level'])) {
            return max(0, (int) $item['level']);
        }

        return $label === 'title' ? 0 : 1;
    }

    /**
     * @param  array<string, mixed>  $tableData
     * @return array<int, array<string, mixed>>
     */
    private function tableCells(array $tableData): array
    {
        $cells = [];

        foreach ((array) ($tableData['table_cells'] ?? []) as $cell) {
            if (! is_array($cell)) {
                continue;
            }

            $text = trim((string) ($cell['text'] ?? ''));

            if ($text === '') {
                continue;
            }

            $cells[] = [
                'row' => (int) ($cell['row_idx'] ?? 0),
                'column' => (int) ($cell['col_idx'] ?? 0),
                'row_span' => (int) ($cell['row_span'] ?? 1),
                'column_span' => (int) ($cell['col_span'] ?? 1),
                'column_header' => (bool) ($cell['column_header'] ?? false),
                'row_header' => (bool) ($cell['row_header'] ?? false),
                'text' => $text,
            ];
        }

        return $cells;
    }
}
