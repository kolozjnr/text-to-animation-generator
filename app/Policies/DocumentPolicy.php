<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user may view the document.
     */
    public function view(User $user, Document $document): bool
    {
        return $document->user_id === $user->id;
    }
}
