<?php

namespace App\Models;

use App\Enums\ExtractionStatus;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'original_name',
    'mime_type',
    'size_bytes',
    'checksum',
    'storage_provider',
    'storage_public_id',
    'storage_asset_id',
    'storage_url',
    'storage_resource_type',
    'storage_format',
    'storage_metadata',
    'extraction_status',
    'page_count',
    'extracted_content_path',
    'extraction_metadata',
    'extraction_started_at',
    'extracted_at',
    'failure_reason',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    /**
     * Storage references and private artifact paths must never be serialized
     * directly to a client.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'storage_public_id',
        'storage_asset_id',
        'storage_url',
        'storage_metadata',
        'extracted_content_path',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<GeneratedLesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(GeneratedLesson::class, 'source_document_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'extraction_status' => ExtractionStatus::class,
            'size_bytes' => 'integer',
            'page_count' => 'integer',
            'storage_metadata' => 'array',
            'extraction_metadata' => 'array',
            'extraction_started_at' => 'datetime',
            'extracted_at' => 'datetime',
        ];
    }
}
