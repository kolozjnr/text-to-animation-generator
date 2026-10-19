<?php

namespace Tests\Feature;

use App\Services\Extraction\DoclingResultNormalizer;
use Tests\TestCase;

class DoclingResultNormalizerTest extends TestCase
{
    public function test_it_normalizes_headings_tables_and_page_quality(): void
    {
        $json = json_decode(file_get_contents(base_path('tests/Fixtures/docling/simple.json')), true, flags: JSON_THROW_ON_ERROR);
        $result = (new DoclingResultNormalizer)->normalize($json, "Lesson title\nSection one\nFirst paragraph.\nSecond page paragraph.");

        $this->assertSame(2, $result['quality']['page_count']);
        $this->assertSame(2, $result['quality']['pages_with_text']);
        $this->assertSame([], $result['quality']['empty_pages']);
        $this->assertSame(2, $result['quality']['heading_count']);
        $this->assertSame(1, $result['quality']['table_count']);
        $this->assertSame([], $result['warnings']);

        $this->assertSame('Lesson title', $result['headings'][0]['text']);
        $this->assertSame(0, $result['headings'][0]['level']);
        $this->assertSame('Section one', $result['headings'][1]['text']);
        $this->assertSame(1, $result['headings'][1]['level']);
        $this->assertCount(1, $result['tables']);
        $this->assertCount(4, $result['tables'][0]['cells']);
        $this->assertSame(2, $result['tables'][0]['page_number']);
        $this->assertCount(2, $result['pages']);
    }

    public function test_it_flags_pages_without_text(): void
    {
        $json = json_decode(file_get_contents(base_path('tests/Fixtures/docling/partial.json')), true, flags: JSON_THROW_ON_ERROR);
        $result = (new DoclingResultNormalizer)->normalize($json, '');

        $this->assertSame([1], $result['quality']['empty_pages']);
        $this->assertContains('Some pages did not contain extractable text.', $result['warnings']);
        $this->assertContains('No text was extracted from the document.', $result['warnings']);
    }
}
