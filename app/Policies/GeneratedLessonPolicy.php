<?php

namespace App\Policies;

use App\Models\GeneratedLesson;
use App\Models\User;

class GeneratedLessonPolicy
{
    /**
     * Determine whether the user may view the generated lesson.
     */
    public function view(User $user, GeneratedLesson $lesson): bool
    {
        return $lesson->user_id === $user->id;
    }
}
