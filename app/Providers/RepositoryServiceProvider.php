<?php

namespace App\Providers;

use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Repositories\Eloquent\DocumentRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register repository bindings.
     */
    public function register(): void
    {
        $this->app->bind(DocumentRepositoryInterface::class, DocumentRepository::class);
    }
}
