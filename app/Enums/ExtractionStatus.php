<?php

namespace App\Enums;

enum ExtractionStatus: string
{
    case Pending = 'pending';
    case Extracting = 'extracting';
    case Completed = 'completed';
    case NeedsReview = 'needs_review';
    case Failed = 'failed';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::NeedsReview, self::Failed], true);
    }
}
