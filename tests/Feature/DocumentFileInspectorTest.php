<?php

namespace Tests\Feature;

use App\Exceptions\InvalidDocumentException;
use App\Services\Documents\DocumentFileInspector;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class DocumentFileInspectorTest extends TestCase
{
    /** @var array<int, string> */
    private array $tempPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->tempPaths as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    public function test_a_pdf_with_a_valid_signature_is_accepted(): void
    {
        $this->inspector()->inspect($this->uploaded('guide.pdf', "%PDF-1.4\n1 0 obj\n%%EOF", 'application/pdf'));

        $this->addToAssertionCount(1);
    }

    public function test_a_pdf_without_a_signature_is_rejected(): void
    {
        $this->expectException(InvalidDocumentException::class);
        $this->expectExceptionMessage('The file is not a valid PDF document.');

        $this->inspector()->inspect($this->uploaded('guide.pdf', 'Not a PDF', 'application/pdf'));
    }

    public function test_a_valid_docx_is_accepted(): void
    {
        $this->inspector()->inspect($this->docx('manual.docx'));

        $this->addToAssertionCount(1);
    }

    public function test_a_malformed_docx_is_rejected(): void
    {
        $this->expectException(InvalidDocumentException::class);
        $this->expectExceptionMessage('The file is not a valid DOCX document.');

        $this->inspector()->inspect($this->uploaded(
            'broken.docx',
            'This is not a ZIP archive.',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ));
    }

    public function test_a_docx_with_a_macro_is_rejected(): void
    {
        $this->expectException(InvalidDocumentException::class);
        $this->expectExceptionMessage('Macro-enabled DOCX files are not supported.');

        $this->inspector()->inspect($this->docx('manual.docx', macro: true));
    }

    public function test_a_docx_with_external_relationships_is_rejected(): void
    {
        $this->expectException(InvalidDocumentException::class);
        $this->expectExceptionMessage('The DOCX contains unsupported external links.');

        $this->inspector()->inspect($this->docx('manual.docx', externalLink: true));
    }

    public function test_a_docx_archive_bomb_is_rejected_by_ratio(): void
    {
        config(['documents.docx.max_compression_ratio' => 2]);

        $this->expectException(InvalidDocumentException::class);
        $this->expectExceptionMessage('The DOCX compression ratio is unsafe.');

        $this->inspector()->inspect($this->docx('bomb.docx', largeEntry: true));
    }

    public function test_a_docx_with_too_many_entries_is_rejected(): void
    {
        config(['documents.docx.max_entries' => 2]);

        $this->expectException(InvalidDocumentException::class);
        $this->expectExceptionMessage('The DOCX contains too many archive entries.');

        $this->inspector()->inspect($this->docx('manual.docx', extraEntry: true));
    }

    public function test_a_plain_text_file_is_accepted(): void
    {
        $this->inspector()->inspect($this->uploaded('notes.txt', "Hello\nworld", 'text/plain'));

        $this->addToAssertionCount(1);
    }

    public function test_a_utf16_text_file_with_a_bom_is_accepted(): void
    {
        $content = "\xFF\xFE".mb_convert_encoding('Hello UTF-16', 'UTF-16LE', 'UTF-8');

        $this->inspector()->inspect($this->uploaded('notes.txt', $content, 'text/plain'));

        $this->addToAssertionCount(1);
    }

    public function test_binary_text_is_rejected(): void
    {
        $this->expectException(InvalidDocumentException::class);
        $this->expectExceptionMessage('The text file contains binary or unsupported control characters.');

        $this->inspector()->inspect($this->uploaded('notes.txt', "Hello\x00\x01world", 'text/plain'));
    }

    public function test_an_empty_text_file_is_rejected(): void
    {
        $this->expectException(InvalidDocumentException::class);
        $this->expectExceptionMessage('The document cannot be empty.');

        $this->inspector()->inspect($this->uploaded('notes.txt', '   ', 'text/plain'));
    }

    private function inspector(): DocumentFileInspector
    {
        return $this->app->make(DocumentFileInspector::class);
    }

    private function uploaded(string $name, string $content, string $mime): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'document-test-');
        $this->assertNotFalse($path);
        file_put_contents($path, $content);
        $this->tempPaths[] = $path;

        return new UploadedFile($path, $name, $mime, null, true);
    }

    private function docx(
        string $name,
        bool $macro = false,
        bool $externalLink = false,
        bool $largeEntry = false,
        bool $extraEntry = false,
    ): UploadedFile {
        $path = tempnam(sys_get_temp_dir(), 'docx-test-');
        $this->assertNotFalse($path);
        $this->tempPaths[] = $path;

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

        if ($largeEntry) {
            $zip->addFromString('word/media/bomb.bin', str_repeat('A', 1_000_000));
        }

        if ($extraEntry) {
            $zip->addFromString('word/extra.xml', '<extra />');
        }

        $zip->close();

        return new UploadedFile(
            $path,
            $name,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true,
        );
    }
}
