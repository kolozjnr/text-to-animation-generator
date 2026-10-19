<?php

namespace App\Repositories\Contracts;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface DocumentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Document;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Document $document, array $attributes): Document;

    /**
     * @return Collection<int, Document>
     */
    public function recentForUser(User $user, int $limit = 25): Collection;
}
