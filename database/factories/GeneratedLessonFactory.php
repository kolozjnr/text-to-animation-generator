<?php

namespace Database\Factories;

use App\Enums\LessonStatus;
use App\Models\Document;
use App\Models\GeneratedLesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeneratedLesson>
 */
class GeneratedLessonFactory extends Factory
{
    protected $model = GeneratedLesson::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source_document_id' => Document::factory(),
            'title' => fake()->sentence(4),
            'generation_status' => LessonStatus::Pending,
            'lesson_script_path' => null,
            'video_url' => null,
            'video_public_id' => null,
            'video_format' => null,
            'video_duration' => null,
            'narration_url' => null,
            'narration_public_id' => null,
            'generation_metadata' => null,
            'failure_reason' => null,
        ];
    }
}
