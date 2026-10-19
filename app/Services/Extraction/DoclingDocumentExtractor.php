<?php

namespace App\Services\Extraction;

use App\Contracts\Documents\DocumentExtractor;
use App\Data\ExtractionResult;
use App\Exceptions\DocumentExtractionException;
use App\Support\FailureReason;
use Illuminate\Support\Facades\Http;
use JsonException;
use Throwable;

class DoclingDocumentExtractor implements DocumentExtractor
{
    public function __construct(
        private readonly DoclingResultNormalizer $normalizer,
    ) {}

    public function supports(string $extension): bool
    {
        return in_array(strtolower($extension), ['pdf', 'docx'], true);
    }

    public function extract(string $filePath, string $originalName, string $extension): ExtractionResult
    {
        $extension = strtolower($extension);

        if (! $this->supports($extension)) {
            throw new DocumentExtractionException('The document type is not supported by the extraction service.');
        }

        if (! is_file($filePath)) {
            throw new DocumentExtractionException('The document could not be read for extraction.');
        }

        $baseUrl = rtrim((string) config('docling.base_url', ''), '/');

        if ($baseUrl === '') {
            throw new DocumentExtractionException('The extraction service is not configured.');
        }

        $contents = file_get_contents($filePath);

        if ($contents === false) {
            throw new DocumentExtractionException('The document could not be read for extraction.');
        }

        $multipart = [
            [
                'name' => 'files',
                'contents' => $contents,
                'filename' => basename($originalName) ?: 'document.'.$extension,
                'headers' => ['Content-Type' => $this->mimeType($extension)],
            ],
            ['name' => 'from_formats', 'contents' => $extension],
            ['name' => 'to_formats', 'contents' => 'json'],
            ['name' => 'to_formats', 'contents' => 'text'],
            ['name' => 'do_table_structure', 'contents' => $this->boolean(config('docling.table_structure', true))],
            ['name' => 'do_pdf_heading_hierarchy', 'contents' => $this->boolean(config('docling.heading_hierarchy', true))],
            ['name' => 'include_images', 'contents' => 'false'],
            ['name' => 'abort_on_error', 'contents' => 'false'],
            ['name' => 'do_ocr', 'contents' => $this->boolean(config('docling.ocr_enabled', false))],
            ['name' => 'document_timeout', 'contents' => (string) max(1, (int) config('docling.document_timeout', 180))],
        ];

        if ((bool) config('docling.ocr_enabled', false)) {
            $multipart[] = [
                'name' => 'ocr_lang',
                'contents' => (string) config('docling.ocr_lang', 'en'),
            ];
        }

        try {
            $request = Http::withOptions([
                'multipart' => $multipart,
                'verify' => (bool) config('docling.verify_ssl', true),
            ])
                ->asMultipart()
                ->timeout(max(1, (int) config('docling.timeout', 240)))
                ->connectTimeout(max(1, (int) config('docling.connect_timeout', 5)))
                ->acceptJson();

            $apiKey = (string) config('docling.api_key', '');

            if ($apiKey !== '') {
                $request = $request->withHeaders(['X-Api-Key' => $apiKey]);
            }

            $response = $request->post($baseUrl.'/v1/convert/file');
            $response->throw();
            $payload = $response->json();
        } catch (Throwable) {
            throw new DocumentExtractionException('The extraction service could not process the document.');
        }

        if (! is_array($payload)) {
            throw new DocumentExtractionException('The extraction service returned an invalid response.');
        }

        $status = strtolower((string) ($payload['status'] ?? 'failure'));

        if (in_array($status, ['failure', 'failed', 'skipped'], true)) {
            throw new DocumentExtractionException('The extraction service could not process the document.');
        }

        $document = $payload['document'] ?? [];

        if (! is_array($document)) {
            $document = [];
        }

        $text = $this->textContent($document);
        $jsonContent = $this->jsonContent($document['json_content'] ?? null);
        $normalized = $this->normalizer->normalize($jsonContent, $text);

        $warnings = $this->warnings($payload['errors'] ?? [], $normalized['warnings']);

        if ($status === 'partial_success') {
            array_unshift($warnings, 'The extraction service returned a partial result.');
        }

        $pageCount = isset($normalized['quality']['page_count'])
            ? (int) $normalized['quality']['page_count']
            : null;

        $maxPages = max(1, (int) config('docling.max_pages', 200));

        if ($pageCount !== null && $pageCount > $maxPages) {
            throw new DocumentExtractionException('The document exceeds the allowed page limit.');
        }

        return new ExtractionResult(
            text: $text,
            pages: $normalized['pages'],
            headings: $normalized['headings'],
            tables: $normalized['tables'],
            warnings: $warnings,
            quality: $normalized['quality'],
            pageCount: $pageCount,
            ocrUsed: (bool) config('docling.ocr_enabled', false),
            engine: 'docling',
            engineStatus: $status,
            metadata: array_filter([
                'processing_time' => $payload['processing_time'] ?? null,
                'service_status' => $status,
                'errors_count' => is_array($payload['errors'] ?? null) ? count($payload['errors']) : 0,
            ], static fn (mixed $value): bool => $value !== null),
        );
    }

    /**
     * @param  array<string, mixed>  $document
     */
    private function textContent(array $document): string
    {
        foreach (['text_content', 'md_content'] as $key) {
            if (isset($document[$key]) && is_string($document[$key])) {
                return trim($document[$key]);
            }
        }

        return '';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function jsonContent(mixed $content): ?array
    {
        if (is_array($content)) {
            return $content;
        }

        if (! is_string($content) || trim($content) === '') {
            return null;
        }

        try {
            $decoded = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<int, string>  $normalizerWarnings
     * @return array<int, string>
     */
    private function warnings(mixed $errors, array $normalizerWarnings): array
    {
        $warnings = $normalizerWarnings;

        foreach ((array) $errors as $error) {
            $message = is_array($error)
                ? (string) ($error['message'] ?? $error['error'] ?? '')
                : (string) $error;

            if (trim($message) !== '') {
                $warnings[] = FailureReason::sanitize($message);
            }
        }

        return array_values(array_unique(array_filter($warnings)));
    }

    private function boolean(mixed $value): string
    {
        return $value ? 'true' : 'false';
    }

    private function mimeType(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };
    }
}
