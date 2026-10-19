<?php

namespace Tests\Feature;

use App\Enums\ExtractionStatus;
use App\Jobs\ExtractSourceDocument;
use App\Models\Document;
use App\Services\Extraction\DocumentExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class ExtractSourceDocumentJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_job_runs_extraction_for_pending_documents(): void
    {
        $document = Document::factory()->create(['extraction_status' => ExtractionStatus::Pending]);

        $this->mock(DocumentExtractionService::class, function (MockInterface $mock) use ($document): void {
            $mock->shouldReceive('extract')
                ->once()
                ->with(Mockery::on(fn (Document $candidate): bool => $candidate->id === $document->id))
                ->andReturn($document);
        });

        $job = new ExtractSourceDocument($document->id);
        $job->handle($this->app->make(DocumentExtractionService::class));

        $this->addToAssertionCount(1);
    }

    public function test_a_failed_extraction_is_released_for_a_bounded_retry(): void
    {
        $document = Document::factory()->create(['extraction_status' => ExtractionStatus::Pending]);
        $failed = $document->forceFill(['extraction_status' => ExtractionStatus::Failed]);

        $this->mock(DocumentExtractionService::class, function (MockInterface $mock) use ($failed): void {
            $mock->shouldReceive('extract')->once()->andReturn($failed);
        });

        $job = (new ExtractSourceDocument($document->id))->withFakeQueueInteractions();
        $job->handle($this->app->make(DocumentExtractionService::class));

        $this->assertSame(30, $job->job->releaseDelay);
    }

    public function test_terminal_documents_are_not_re_extracted(): void
    {
        $document = Document::factory()->create(['extraction_status' => ExtractionStatus::Completed]);

        $this->mock(DocumentExtractionService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('extract');
        });

        $job = new ExtractSourceDocument($document->id);
        $job->handle($this->app->make(DocumentExtractionService::class));

        $this->addToAssertionCount(1);
    }

    public function test_a_failed_job_records_a_safe_reason(): void
    {
        $document = Document::factory()->create(['extraction_status' => ExtractionStatus::Extracting]);

        $job = new ExtractSourceDocument($document->id);
        $job->failed(new RuntimeException('database path C:\\secret\\document.pdf'));

        $document->refresh();

        $this->assertSame(ExtractionStatus::Failed, $document->extraction_status);
        $this->assertSame('The document could not be processed.', $document->failure_reason);
        $this->assertStringNotContainsString('secret', (string) $document->failure_reason);
    }

    public function test_the_job_uses_the_configured_queue(): void
    {
        config(['documents.queue' => 'source-documents']);

        $job = new ExtractSourceDocument(123);

        $this->assertSame('source-documents', $job->queue);
    }
}
