<?php

namespace App\Jobs;

use App\Enums\ExtractionStatus;
use App\Models\Document;
use App\Services\Extraction\DocumentExtractionService;
use App\Support\FailureReason;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ExtractSourceDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public function __construct(
        public readonly int $documentId,
    ) {
        $this->onQueue((string) config('documents.queue', 'documents'));
    }

    public function handle(DocumentExtractionService $extraction): void
    {
        $document = Document::query()->find($this->documentId);

        if (! $document) {
            return;
        }

        if (in_array($document->extraction_status, [ExtractionStatus::Completed, ExtractionStatus::NeedsReview], true)) {
            return;
        }

        $result = $extraction->extract($document);

        if ($result->extraction_status === ExtractionStatus::Failed && $this->attempts() < $this->tries) {
            $this->release($this->retryDelay());
        }
    }

    /**
     * Determine the delay before the next bounded extraction attempt.
     */
    private function retryDelay(): int
    {
        $index = min(
            max(0, $this->attempts() - 1),
            max(0, count($this->backoff) - 1),
        );

        return (int) ($this->backoff[$index] ?? 30);
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('extract-document-'.$this->documentId))->expireAfter(600),
        ];
    }

    public function failed(Throwable $exception): void
    {
        Document::query()
            ->whereKey($this->documentId)
            ->update([
                'extraction_status' => ExtractionStatus::Failed->value,
                'failure_reason' => FailureReason::fromThrowable($exception),
            ]);
    }
}
