<?php

namespace App\Models;

use App\Enums\LessonStatus;
use Database\Factories\GeneratedLessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'source_document_id',
    'title',
    'generation_status',
    'lesson_script_path',
    'video_url',
    'video_public_id',
    'video_format',
    'video_duration',
    'narration_url',
    'narration_public_id',
    'generation_metadata',
    'failure_reason',
])]
class GeneratedLesson extends Model
{
    /** @use HasFactory<GeneratedLessonFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'lesson_script_path',
        'video_public_id',
        'narration_public_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'source_document_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'generation_status' => LessonStatus::class,
            'video_duration' => 'decimal:2',
            'generation_metadata' => 'array',
        ];
    }
}
