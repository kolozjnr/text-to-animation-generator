<?php

namespace Tests\Feature;

use App\Enums\LessonStatus;
use App\Models\Document;
use App\Models\GeneratedLesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneratedLessonTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_lessons_belong_to_a_user_and_source_document(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create();
        $lesson = GeneratedLesson::factory()->for($user)->create([
            'source_document_id' => $document->id,
            'generation_status' => LessonStatus::Pending,
            'generation_metadata' => ['scene_count' => 3],
        ]);

        $this->assertTrue($lesson->user->is($user));
        $this->assertTrue($lesson->sourceDocument->is($document));
        $this->assertTrue($document->lessons->contains($lesson));
        $this->assertSame(LessonStatus::Pending, $lesson->generation_status);
        $this->assertSame(['scene_count' => 3], $lesson->generation_metadata);
    }

    public function test_only_the_owner_can_view_a_generated_lesson(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $document = Document::factory()->for($owner)->create();
        $lesson = GeneratedLesson::factory()->for($owner)->create([
            'source_document_id' => $document->id,
        ]);

        $this->assertTrue($owner->can('view', $lesson));
        $this->assertFalse($other->can('view', $lesson));
    }
}
