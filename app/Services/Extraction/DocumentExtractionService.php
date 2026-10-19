<?php

namespace App\Services\Extraction;

use App\Contracts\Documents\DocumentAssetStore;
use App\Data\ExtractionResult;
use App\Enums\ExtractionStatus;
use App\Exceptions\DocumentExtractionException;
use App\Models\Document;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Services\Documents\DocumentFileInspector;
use App\Support\FailureReason;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Throwable;

class DocumentExtractionService
{
    public function __construct(
        private readonly DocumentRepositoryInterface $documents,
        private readonly DocumentAssetStore $assets,
        private readonly DocumentFileInspector $inspector,
        private readonly DoclingDocumentExtractor $docling,
        private readonly PlainTextDocumentExtractor $plainText,
    ) {}

    public function extract(Document $document): Document
    {
        $publicId = (string) ($document->storage_public_id ?? '');
        $format = (string) ($document->storage_format ?? '');
        $resourceType = (string) ($document->storage_resource_type ?? 'raw');

        if ($publicId === '' || $format === '' || $resourceType === '') {
            return $this->fail($document, 'The stored document reference is unavailable.');
        }

        $document = $this->documents->update($document, [
            'extraction_status' => ExtractionStatus::Extracting,
            'extraction_started_at' => now(),
            'failure_reason' => null,
        ]);

        $tempPath = null;
        $artifactPath = null;
        $artifactStored = false;

        try {
            $tempPath = $this->assets->downloadToTemp($publicId, $format, $resourceType);

            $extension = strtolower(pathinfo($document->original_name, PATHINFO_EXTENSION));

            if ($extension === '') {
                $extension = strtolower($format);
            }

            $this->inspector->inspectPath($tempPath, $extension);

            $result = $extension === 'txt'
                ? $this->plainText->extract($tempPath, $document->original_name, $extension)
                : $this->docling->extract($tempPath, $document->original_name, $extension);

            $text = trim($result->text);
            $warnings = [];
            $status = $this->status($result, $text, $warnings);
            $completedAt = now();
            $pageCount = $result->pageCount ?? count($result->pages);

            if ($text !== '') {
                $artifactPath = $this->storeArtifact($document, $result, $text, $warnings, $completedAt);
                $artifactStored = true;
            }

            $document = $this->documents->update($document, [
                'extraction_status' => $status,
                'page_count' => $pageCount,
                'extracted_content_path' => $artifactPath,
                'extraction_metadata' => [
                    'engine' => $result->engine,
                    'engine_status' => $result->engineStatus,
                    'ocr_used' => $result->ocrUsed,
                    'page_count' => $pageCount,
                    'character_count' => mb_strlen($text),
                    'heading_count' => count($result->headings),
                    'table_count' => count($result->tables),
                    'warnings' => $warnings,
                    'quality' => $result->quality,
                    'processing_time' => $result->metadata['processing_time'] ?? null,
                    'extracted_at' => $completedAt->toIso8601String(),
                ],
                'extracted_at' => $completedAt,
                'failure_reason' => $status === ExtractionStatus::NeedsReview
                    ? FailureReason::sanitize($warnings[0] ?? 'The extraction requires review.')
                    : null,
            ]);

            return $document;
        } catch (Throwable $exception) {
            if ($artifactStored && $artifactPath !== null) {
                Storage::disk($this->disk())->delete($artifactPath);
            }

            $reason = FailureReason::fromThrowable($exception);

            Log::warning('Document extraction failed.', [
                'document_id' => $document->id,
                'exception' => $exception::class,
            ]);

            return $this->fail($document, $reason);
        } finally {
            if ($tempPath !== null && is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * @param  array<int, string>  $warnings
     */
    private function status(ExtractionResult $result, string $text, array &$warnings): ExtractionStatus
    {
        $warnings = array_values(array_unique(array_filter(
            $result->warnings,
            static fn (mixed $warning): bool => is_string($warning) && trim($warning) !== '',
        )));

        $status = ExtractionStatus::Completed;

        if ($result->engineStatus === 'partial_success') {
            $status = ExtractionStatus::NeedsReview;
            array_unshift($warnings, 'The extraction service returned a partial result.');
        }

        if ($text === '') {
            $status = ExtractionStatus::NeedsReview;
            $warnings[] = 'No text was extracted. The document may be scanned or image-based; OCR may be required.';
        } elseif (mb_strlen($text) < max(1, (int) config('documents.extraction.min_text_characters', 20))) {
            $status = ExtractionStatus::NeedsReview;
            $warnings[] = 'Very little text was extracted from the document.';
        }

        if ($warnings !== []) {
            $status = ExtractionStatus::NeedsReview;
        }

        $warnings = array_values(array_unique($warnings));

        return $status;
    }

    /**
     * @param  array<int, string>  $warnings
     */
    private function storeArtifact(
        Document $document,
        ExtractionResult $result,
        string $text,
        array $warnings,
        \DateTimeInterface $completedAt,
    ): string {
        $artifact = [
            'document_id' => $document->id,
            'source_checksum' => $document->checksum,
            'engine' => $result->engine,
            'engine_status' => $result->engineStatus,
            'ocr_used' => $result->ocrUsed,
            'extracted_at' => $completedAt->format(DATE_ATOM),
            'text' => $text,
            'pages' => $result->pages,
            'headings' => $result->headings,
            'tables' => $result->tables,
            'warnings' => $warnings,
            'quality' => $result->quality,
        ];

        try {
            $encoded = json_encode(
                $artifact,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            throw new DocumentExtractionException('The extracted content could not be stored.');
        }

        if (strlen($encoded) > max(1, (int) config('documents.extraction.max_artifact_bytes', 10_485_760))) {
            throw new DocumentExtractionException('The extracted content is too large to store.');
        }

        $path = trim((string) config('documents.extraction.directory', 'extractions'), '/')
            .'/'.$document->user_id.'/'.$document->id.'.json';

        if (! Storage::disk($this->disk())->put($path, $encoded)) {
            throw new DocumentExtractionException('The extracted content could not be stored.');
        }

        return $path;
    }

    private function fail(Document $document, string $reason): Document
    {
        return $this->documents->update($document, [
            'extraction_status' => ExtractionStatus::Failed,
            'extracted_content_path' => null,
            'extracted_at' => null,
            'failure_reason' => FailureReason::sanitize($reason),
        ]);
    }

    private function disk(): string
    {
        return (string) config('documents.extraction.disk', 'local');
    }
}
