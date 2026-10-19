<?php

namespace App\Repositories\Eloquent;

use App\Models\Document;
use App\Models\User;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class DocumentRepository implements DocumentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Document
    {
        return Document::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Document $document, array $attributes): Document
    {
        $document->fill($attributes)->save();

        return $document->refresh();
    }

    /**
     * @return Collection<int, Document>
     */
    public function recentForUser(User $user, int $limit = 25): Collection
    {
        return Document::query()
            ->select([
                'id',
                'user_id',
                'original_name',
                'size_bytes',
                'extraction_status',
                'page_count',
                'extraction_metadata',
                'failure_reason',
                'created_at',
            ])
            ->where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get();
    }
}
