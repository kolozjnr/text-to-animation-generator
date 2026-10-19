<?php

namespace Tests\Feature;

use App\Exceptions\DocumentExtractionException;
use App\Services\Extraction\DoclingDocumentExtractor;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DoclingDocumentExtractorTest extends TestCase
{
    /** @var array<int, string> */
    private array $tempPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'docling.base_url' => 'http://docling.test',
            'docling.api_key' => 'test-key',
            'docling.timeout' => 30,
            'docling.connect_timeout' => 5,
            'docling.document_timeout' => 20,
            'docling.max_pages' => 200,
            'docling.ocr_enabled' => false,
            'docling.table_structure' => true,
            'docling.heading_hierarchy' => true,
            'docling.verify_ssl' => true,
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->tempPaths as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    public function test_it_sends_the_document_to_docling_and_normalizes_the_result(): void
    {
        $json = json_decode(file_get_contents(base_path('tests/Fixtures/docling/simple.json')), true, flags: JSON_THROW_ON_ERROR);

        Http::fake([
            'http://docling.test/v1/convert/file' => Http::response([
                'status' => 'success',
                'document' => [
                    'text_content' => "Lesson title\nSection one\nFirst paragraph.\nSecond page paragraph.",
                    'json_content' => $json,
                ],
                'errors' => [],
                'processing_time' => 1.25,
            ]),
        ]);

        $result = $this->extractor()->extract($this->pdf(), 'guide.pdf', 'pdf');

        $this->assertSame("Lesson title\nSection one\nFirst paragraph.\nSecond page paragraph.", $result->text);
        $this->assertSame('docling', $result->engine);
        $this->assertSame('success', $result->engineStatus);
        $this->assertSame(2, $result->pageCount);
        $this->assertCount(2, $result->headings);
        $this->assertCount(1, $result->tables);
        $this->assertSame([], $result->warnings);
        $this->assertSame(1.25, $result->metadata['processing_time']);

        Http::assertSent(function (Request $request): bool {
            $body = $request->body();

            return $request->url() === 'http://docling.test/v1/convert/file'
                && $request->hasHeader('X-Api-Key', 'test-key')
                && $request->isMultipart()
                && str_contains($body, 'name="files"')
                && str_contains($body, 'filename="guide.pdf"')
                && str_contains($body, 'Content-Type: application/pdf')
                && substr_count($body, 'name="to_formats"') === 2
                && str_contains($body, 'name="from_formats"')
                && str_contains($body, 'pdf')
                && str_contains($body, 'name="do_ocr"')
                && str_contains($body, 'false');
        });
    }

    public function test_partial_docling_results_are_returned_with_warnings(): void
    {
        $json = json_decode(file_get_contents(base_path('tests/Fixtures/docling/partial.json')), true, flags: JSON_THROW_ON_ERROR);

        Http::fake([
            '*' => Http::response([
                'status' => 'partial_success',
                'document' => [
                    'text_content' => '',
                    'json_content' => $json,
                ],
                'errors' => [
                    ['message' => 'The parser could not read one section.'],
                ],
            ]),
        ]);

        $result = $this->extractor()->extract($this->pdf(), 'scanned.pdf', 'pdf');

        $this->assertSame('partial_success', $result->engineStatus);
        $this->assertContains('The extraction service returned a partial result.', $result->warnings);
        $this->assertContains('The parser could not read one section.', $result->warnings);
        $this->assertContains('No text was extracted from the document.', $result->warnings);
    }

    public function test_a_docling_failure_status_is_reported_safely(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'failure',
                'errors' => [['message' => 'Internal parser path C:\\secret\\file.pdf']],
            ]),
        ]);

        $this->expectException(DocumentExtractionException::class);
        $this->expectExceptionMessage('The extraction service could not process the document.');

        $this->extractor()->extract($this->pdf(), 'guide.pdf', 'pdf');
    }

    public function test_docling_http_errors_do_not_expose_internal_details(): void
    {
        Http::fake([
            '*' => Http::response('upstream failure', 500),
        ]);

        try {
            $this->extractor()->extract($this->pdf(), 'guide.pdf', 'pdf');
            $this->fail('Expected an extraction failure.');
        } catch (DocumentExtractionException $exception) {
            $this->assertSame('The extraction service could not process the document.', $exception->getMessage());
            $this->assertStringNotContainsString('upstream failure', $exception->getMessage());
        }
    }

    public function test_documents_over_the_page_limit_are_rejected(): void
    {
        config(['docling.max_pages' => 1]);

        $json = json_decode(file_get_contents(base_path('tests/Fixtures/docling/simple.json')), true, flags: JSON_THROW_ON_ERROR);

        Http::fake([
            '*' => Http::response([
                'status' => 'success',
                'document' => [
                    'text_content' => 'Text',
                    'json_content' => $json,
                ],
            ]),
        ]);

        $this->expectException(DocumentExtractionException::class);
        $this->expectExceptionMessage('The document exceeds the allowed page limit.');

        $this->extractor()->extract($this->pdf(), 'guide.pdf', 'pdf');
    }

    private function extractor(): DoclingDocumentExtractor
    {
        return $this->app->make(DoclingDocumentExtractor::class);
    }

    private function pdf(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'docling-document-');
        $this->assertNotFalse($path);
        file_put_contents($path, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
        $this->tempPaths[] = $path;

        return $path;
    }
}
