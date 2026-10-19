<?php

namespace App\Providers;

use App\Contracts\Cloudinary\CloudinaryClient;
use App\Contracts\Documents\DocumentAssetStore;
use App\Services\Cloudinary\CloudinaryDocumentAssetStore;
use App\Services\Cloudinary\SdkCloudinaryClient;
use Illuminate\Support\ServiceProvider;

class DocumentServiceProvider extends ServiceProvider
{
    /**
     * Register document storage and extraction bindings.
     */
    public function register(): void
    {
        $this->app->bind(CloudinaryClient::class, SdkCloudinaryClient::class);
        $this->app->bind(DocumentAssetStore::class, CloudinaryDocumentAssetStore::class);
    }
}
