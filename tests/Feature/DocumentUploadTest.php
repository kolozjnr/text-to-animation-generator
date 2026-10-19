<?php

namespace Tests\Feature;

use App\Contracts\Documents\DocumentAssetStore;
use App\Enums\ExtractionStatus;
use App\Jobs\ExtractSourceDocument;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\FakeDocumentAssetStore;
use Tests\TestCase;
use ZipArchive;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    private FakeDocumentAssetStore $assets;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->assets = new FakeDocumentAssetStore;
        $this->app->instance(DocumentAssetStore::class, $this->assets);
    }

    public function test_guests_cannot_upload_documents(): void
    {
        $this->post(route('documents.store'), [
            'document' => UploadedFile::fake()->createWithContent('notes.txt', 'Hello world'),
        ])->assertRedirect('/login');

        $this->assertDatabaseCount('documents', 0);
        Queue::assertNothingPushed();
    }

    public function test_authenticated_users_can_upload_a_txt_document_to_private_storage(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'document' => UploadedFile::fake()->createWithContent('notes.txt', 'Hello world'),
        ]);

        $response
            ->assertRedirect(route('convert'))
            ->assertSessionHas('status', 'Document uploaded. Extraction has been queued.');

        $document = Document::query()->sole();

        $this->assertSame($user->id, $document->user_id);
        $this->assertSame('notes.txt', $document->original_name);
        $this->assertSame('text/plain', $document->mime_type);
        $this->assertSame(11, $document->size_bytes);
        $this->assertSame(ExtractionStatus::Pending, $document->extraction_status);
        $this->assertSame('cloudinary', $document->storage_provider);
        $this->assertSame('raw', $document->storage_resource_type);
        $this->assertSame('txt', $document->storage_format);
        $this->assertStringStartsWith('source-documents/'.$user->id.'/', $document->storage_public_id);
        $this->assertStringEndsWith('.txt', $document->storage_public_id);
        $this->assertNotNull($document->storage_asset_id);
        $this->assertNotNull($document->storage_url);
        $this->assertSame(['delivery_type' => 'private'], $document->storage_metadata);
        $this->assertArrayNotHasKey('stored_path', $document->getAttributes());
        $this->assertArrayNotHasKey('storage_public_id', $document->toArray());
        $this->assertArrayNotHasKey('storage_url', $document->toArray());
        $this->assertArrayNotHasKey('extracted_content_path', $document->toArray());
        $this->assertSame(['notes.txt'], $this->assets->uploaded);

        Queue::assertPushed(ExtractSourceDocument::class, function (ExtractSourceDocument $job) use ($document): bool {
            return $job->documentId === $document->id;
        });
    }

    public function test_authenticated_users_can_upload_a_pdf_document(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('documents.store'), [
            'document' => UploadedFile::fake()->createWithContent('guide.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF"),
        ])->assertRedirect(route('convert'));

        $document = Document::query()->sole();

        $this->assertSame('application/pdf', $document->mime_type);
        $this->assertSame('pdf', $document->storage_format);
        $this->assertSame(ExtractionStatus::Pending, $document->extraction_status);
    }

    public function test_authenticated_users_can_upload_a_docx_document(): void
    {
        $user = User::factory()->create();
        $path = $this->createDocx();

        try {
            $this->actingAs($user)->post(route('documents.store'), [
                'document' => new UploadedFile(
                    $path,
                    'manual.docx',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    null,
                    true,
                ),
            ])->assertRedirect(route('convert'));
        } finally {
            @unlink($path);
        }

        $document = Document::query()->sole();

        $this->assertSame('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $document->mime_type);
        $this->assertSame('docx', $document->storage_format);
        $this->assertSame('raw', $document->storage_resource_type);
    }

    public function test_unsupported_file_types_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('documents.store'), [
            'document' => UploadedFile::fake()->createWithContent('script.php', '<?php echo "no";'),
        ])->assertSessionHasErrors('document');

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], $this->assets->uploaded);
        Queue::assertNothingPushed();
    }

    public function test_empty_files_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('documents.store'), [
            'document' => UploadedFile::fake()->createWithContent('empty.txt', ''),
        ])->assertSessionHasErrors('document');

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], $this->assets->uploaded);
    }

    public function test_oversized_files_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('documents.store'), [
            'document' => UploadedFile::fake()->create('large.pdf', 3 * 1024, 'application/pdf'),
        ])->assertSessionHasErrors('document');

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], $this->assets->uploaded);
    }

    public function test_text_files_with_binary_control_characters_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('documents.store'), [
            'document' => UploadedFile::fake()->createWithContent('notes.txt', "Hello\x00\x01world"),
        ])->assertSessionHasErrors('document');

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], $this->assets->uploaded);
    }

    public function test_pdf_files_without_a_pdf_signature_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('documents.store'), [
            'document' => UploadedFile::fake()->createWithContent('fake.pdf', 'This is not a PDF.'),
        ])->assertSessionHasErrors('document');

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], $this->assets->uploaded);
    }

    public function test_macro_enabled_docx_files_are_rejected(): void
    {
        $user = User::factory()->create();
        $path = $this->createDocx(macro: true);

        try {
            $this->actingAs($user)->post(route('documents.store'), [
                'document' => new UploadedFile(
                    $path,
                    'macro.docx',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    null,
                    true,
                ),
            ])->assertSessionHasErrors('document');
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], $this->assets->uploaded);
    }

    public function test_original_filenames_are_sanitized_before_storage(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('documents.store'), [
            'document' => UploadedFile::fake()->createWithContent('..\\..\\evil.txt', 'Safe content'),
        ])->assertRedirect(route('convert'));

        $document = Document::query()->sole();

        $this->assertSame('evil.txt', $document->original_name);
        $this->assertStringNotContainsString('evil', $document->storage_public_id);
        $this->assertStringNotContainsString('..', $document->storage_public_id);
    }

    public function test_cloudinary_upload_failures_return_a_safe_error(): void
    {
        $user = User::factory()->create();
        $this->assets->failUpload = true;

        $this->actingAs($user)->post(route('documents.store'), [
            'document' => UploadedFile::fake()->createWithContent('notes.txt', 'Hello world'),
        ])
            ->assertRedirect()
            ->assertSessionHas('error', 'The document could not be uploaded to private storage. Please try again.');

        $this->assertDatabaseCount('documents', 0);
        Queue::assertNothingPushed();
    }

    public function test_convert_page_lists_extraction_status_and_metadata(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create([
            'extraction_status' => ExtractionStatus::NeedsReview,
            'page_count' => 3,
            'extraction_metadata' => ['warnings' => ['First warning', 'Second warning']],
            'failure_reason' => 'Some pages did not contain extractable text.',
        ]);

        $this->actingAs($user)->get(route('convert'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Convert')
                ->has('documents', 1)
                ->where('documents.0.id', $document->id)
                ->where('documents.0.extraction_status', 'needs_review')
                ->where('documents.0.page_count', 3)
                ->where('documents.0.warning_count', 2)
                ->where('documents.0.failure_reason', 'Some pages did not contain extractable text.'));
    }

    public function test_users_only_see_their_own_documents(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $ownDocument = Document::factory()->for($user)->create();
        Document::factory()->for($other)->create();

        $this->actingAs($user)->get(route('convert'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('documents', 1)
                ->where('documents.0.id', $ownDocument->id));
    }

    private function createDocx(bool $macro = false, bool $externalLink = false): string
    {
        $path = tempnam(sys_get_temp_dir(), 'docx-test-');
        $zip = new ZipArchive;

        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"></Types>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Hello</w:t></w:r></w:p></w:body></w:document>');

        if ($macro) {
            $zip->addFromString('word/vbaProject.bin', 'macro');
        }

        if ($externalLink) {
            $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0"?><Relationships><Relationship TargetMode="External" Target="https://example.test" /></Relationships>');
        }

        $zip->close();

        return $path;
    }
}
