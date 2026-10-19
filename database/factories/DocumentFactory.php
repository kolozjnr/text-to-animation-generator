<?php

namespace Database\Factories;

use App\Enums\ExtractionStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extension = fake()->randomElement(['pdf', 'docx', 'txt']);
        $publicId = 'source-documents/'.fake()->numberBetween(1, 100).'/'.Str::uuid().'.'.$extension;

        return [
            'user_id' => User::factory(),
            'original_name' => fake()->word().'.'.$extension,
            'mime_type' => match ($extension) {
                'pdf' => 'application/pdf',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                default => 'text/plain',
            },
            'size_bytes' => fake()->numberBetween(1024, 5_000_000),
            'checksum' => hash('sha256', fake()->uuid()),
            'storage_provider' => 'cloudinary',
            'storage_public_id' => $publicId,
            'storage_asset_id' => (string) Str::uuid(),
            'storage_url' => 'https://res.cloudinary.com/example/raw/private/'.$publicId,
            'storage_resource_type' => 'raw',
            'storage_format' => $extension,
            'storage_metadata' => ['delivery_type' => 'private'],
            'extraction_status' => ExtractionStatus::Pending,
            'page_count' => null,
            'extracted_content_path' => null,
            'extraction_metadata' => null,
            'extraction_started_at' => null,
            'extracted_at' => null,
            'failure_reason' => null,
        ];
    }
}
