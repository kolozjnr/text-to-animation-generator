<?php

namespace App\Enums;

enum LessonStatus: string
{
    case Pending = 'pending';
    case Generating = 'generating';
    case Completed = 'completed';
    case NeedsReview = 'needs_review';
    case Failed = 'failed';
}
