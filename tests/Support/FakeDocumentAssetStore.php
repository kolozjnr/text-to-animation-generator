<?php

namespace Tests\Support;

use App\Contracts\Documents\DocumentAssetStore;
use App\Data\CloudinaryAsset;
use App\Exceptions\CloudinaryUploadException;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class FakeDocumentAssetStore implements DocumentAssetStore
{
    /** @var array<int, string> */
    public array $uploaded = [];

    /** @var array<int, array{public_id: string, resource_type: string, delivery_type: string}> */
    public array $deleted = [];

    public ?string $downloadSource = null;

    public bool $failUpload = false;

    public bool $failDownload = false;

    public ?CloudinaryAsset $lastAsset = null;

    public ?string $lastTempPath = null;

    public function upload(UploadedFile $file, User $user): CloudinaryAsset
    {
        if ($this->failUpload) {
            throw new CloudinaryUploadException('The document could not be uploaded to private storage.');
        }

        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $publicId = 'source-documents/'.$user->id.'/'.Str::uuid().'.'.$extension;
        $this->uploaded[] = $file->getClientOriginalName();

        return $this->lastAsset = new CloudinaryAsset(
            publicId: $publicId,
            assetId: (string) Str::uuid(),
            secureUrl: 'https://res.cloudinary.com/example/raw/private/'.$publicId,
            resourceType: 'raw',
            format: $extension,
            bytes: (int) ($file->getSize() ?: 0),
            deliveryType: 'private',
            metadata: ['delivery_type' => 'private'],
        );
    }

    public function downloadToTemp(string $publicId, string $format, string $resourceType): string
    {
        if ($this->failDownload) {
            throw new CloudinaryUploadException('The stored document could not be downloaded.');
        }

        $path = tempnam(sys_get_temp_dir(), 'docling-test-');

        if ($path === false) {
            throw new CloudinaryUploadException('A temporary file could not be created.');
        }

        if ($this->downloadSource !== null && is_file($this->downloadSource)) {
            copy($this->downloadSource, $path);
        } else {
            file_put_contents($path, 'Downloaded document content');
        }

        return $this->lastTempPath = $path;
    }

    public function delete(string $publicId, string $resourceType, string $deliveryType): void
    {
        $this->deleted[] = [
            'public_id' => $publicId,
            'resource_type' => $resourceType,
            'delivery_type' => $deliveryType,
        ];
    }
}
