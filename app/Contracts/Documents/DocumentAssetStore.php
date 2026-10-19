<?php

namespace App\Contracts\Documents;

use App\Data\CloudinaryAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;

interface DocumentAssetStore
{
    public function upload(UploadedFile $file, User $user): CloudinaryAsset;

    public function downloadToTemp(string $publicId, string $format, string $resourceType): string;

    public function delete(string $publicId, string $resourceType, string $deliveryType): void;
}
