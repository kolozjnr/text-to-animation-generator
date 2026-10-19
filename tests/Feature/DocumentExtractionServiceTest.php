<?php

namespace Tests\Feature;

use App\Contracts\Documents\DocumentAssetStore;
use App\Data\ExtractionResult;
use App\Enums\ExtractionStatus;
use App\Models\Document;
use App\Models\User;
use App\Services\Extraction\DoclingDocumentExtractor;
use App\Services\Extraction\DocumentExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\Support\FakeDocumentAssetStore;
use Tests\TestCase;

class DocumentExtractionServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $tempPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        foreach ($this->tempPaths as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    public function test_a_txt_document_is_extracted_into_a_private_artifact(): void
    {
        $content = "# Heading\n\nThis source text is long enough to be considered usable.";
        $assets = $this->assetsWithSource($content);
        $document = $this->document('notes.txt', 'txt');

        $result = $this->service()->extract($document);

        $this->assertSame(ExtractionStatus::Completed, $result->extraction_status);
        $this->assertSame(1, $result->page_count);
        $this->assertNotNull($result->extracted_content_path);
        $this->assertNotNull($result->extraction_started_at);
        $this->assertNotNull($result->extracted_at);
        $this->assertNull($result->failure_reason);
        $this->assertSame('native-text', $result->extraction_metadata['engine']);
        $this->assertSame([], $result->extraction_metadata['warnings']);

        Storage::disk('local')->assertExists($result->extracted_content_path);

        $artifact = json_decode(Storage::disk('local')->get($result->extracted_content_path), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content, $artifact['text']);
        $this->assertSame('Heading', $artifact['headings'][0]['text']);
        $this->assertSame($document->id, $artifact['document_id']);

        $this->assertNotNull($assets->lastTempPath);
        $this->assertFalse(is_file($assets->lastTempPath));
    }

    public function test_short_text_requires_review_but_keeps_the_artifact(): void
    {
        $this->assetsWithSource('Short');
        $document = $this->document('notes.txt', 'txt');

        $result = $this->service()->extract($document);

        $this->assertSame(ExtractionStatus::NeedsReview, $result->extraction_status);
        $this->assertNotNull($result->extracted_content_path);
        $this->assertStringContainsString('Very little text', (string) $result->failure_reason);
        $this->assertContains('Very little text was extracted from the document.', $result->extraction_metadata['warnings']);
        Storage::disk('local')->assertExists($result->extracted_content_path);
    }

    public function test_an_empty_docling_result_requires_review_without_an_artifact(): void
    {
        $this->assetsWithSource("%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
        $document = $this->document('scanned.pdf', 'pdf');

        $this->mock(DoclingDocumentExtractor::class, function (MockInterface $mock): void {
            $mock->shouldReceive('extract')->once()->andReturn(new ExtractionResult(
                text: '',
                pages: [['page_number' => 1, 'headings' => [], 'tables' => []]],
                quality: ['page_count' => 1, 'empty_pages' => [1]],
                pageCount: 1,
                engine: 'docling',
                engineStatus: 'success',
            ));
        });

        $result = $this->service()->extract($document);

        $this->assertSame(ExtractionStatus::NeedsReview, $result->extraction_status);
        $this->assertNull($result->extracted_content_path);
        $this->assertSame(1, $result->page_count);
        $this->assertStringContainsString('No text was extracted', (string) $result->failure_reason);
        $this->assertContains(
            'No text was extracted. The document may be scanned or image-based; OCR may be required.',
            $result->extraction_metadata['warnings'],
        );
    }

    public function test_partial_docling_results_require_review_but_keep_the_artifact(): void
    {
        $this->assetsWithSource("%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
        $document = $this->document('partial.pdf', 'pdf');

        $this->mock(DoclingDocumentExtractor::class, function (MockInterface $mock): void {
            $mock->shouldReceive('extract')->once()->andReturn(new ExtractionResult(
                text: 'This text is long enough to pass the minimum length check.',
                pages: [['page_number' => 1, 'headings' => [], 'tables' => []]],
                warnings: ['One section could not be parsed.'],
                quality: ['page_count' => 1],
                pageCount: 1,
                engine: 'docling',
                engineStatus: 'partial_success',
            ));
        });

        $result = $this->service()->extract($document);

        $this->assertSame(ExtractionStatus::NeedsReview, $result->extraction_status);
        $this->assertNotNull($result->extracted_content_path);
        $this->assertStringContainsString('partial result', (string) $result->failure_reason);
        Storage::disk('local')->assertExists($result->extracted_content_path);
    }

    public function test_download_failures_mark_the_document_failed_with_a_safe_reason(): void
    {
        $assets = new FakeDocumentAssetStore;
        $assets->failDownload = true;
        $this->app->instance(DocumentAssetStore::class, $assets);

        $result = $this->service()->extract($this->document('notes.txt', 'txt'));

        $this->assertSame(ExtractionStatus::Failed, $result->extraction_status);
        $this->assertNull($result->extracted_content_path);
        $this->assertNull($result->extracted_at);
        $this->assertSame('The stored document could not be downloaded.', $result->failure_reason);
    }

    private function assetsWithSource(string $content): FakeDocumentAssetStore
    {
        $path = tempnam(sys_get_temp_dir(), 'document-source-');
        $this->assertNotFalse($path);
        file_put_contents($path, $content);
        $this->tempPaths[] = $path;

        $assets = new FakeDocumentAssetStore;
        $assets->downloadSource = $path;
        $this->app->instance(DocumentAssetStore::class, $assets);

        return $assets;
    }

    private function document(string $originalName, string $format): Document
    {
        $user = User::factory()->create();

        return Document::factory()->for($user)->create([
            'original_name' => $originalName,
            'storage_format' => $format,
            'storage_resource_type' => 'raw',
            'storage_public_id' => 'source-documents/'.$user->id.'/document.'.$format,
        ]);
    }

    private function service(): DocumentExtractionService
    {
        return $this->app->make(DocumentExtractionService::class);
    }
}
