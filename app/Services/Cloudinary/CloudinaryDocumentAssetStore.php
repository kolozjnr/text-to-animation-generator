<?php

namespace App\Services\Cloudinary;

use App\Contracts\Cloudinary\CloudinaryClient;
use App\Contracts\Documents\DocumentAssetStore;
use App\Data\CloudinaryAsset;
use App\Exceptions\CloudinaryUploadException;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class CloudinaryDocumentAssetStore implements DocumentAssetStore
{
    public function __construct(
        private readonly CloudinaryClient $client,
    ) {}

    public function upload(UploadedFile $file, User $user): CloudinaryAsset
    {
        $path = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());

        if ($path === false || ! is_file($path) || $extension === '') {
            throw new CloudinaryUploadException('The document could not be uploaded to private storage.');
        }

        $folder = trim((string) config('services.cloudinary.document_folder', 'source-documents'), '/')
            .'/'.$user->id;

        $options = [
            'resource_type' => (string) config('services.cloudinary.document_resource_type', 'raw'),
            'type' => (string) config('services.cloudinary.document_delivery_type', 'private'),
            'folder' => $folder,
            'public_id' => Str::uuid()->toString().'.'.$extension,
            'use_filename' => false,
            'unique_filename' => false,
            'overwrite' => false,
            'headers' => 'X-Robots-Tag: noindex',
        ];

        try {
            $result = $this->client->upload($path, $options);
        } catch (Throwable) {
            throw new CloudinaryUploadException('The document could not be uploaded to private storage.');
        }

        $publicId = $result['public_id'] ?? null;
        $secureUrl = $result['secure_url'] ?? null;
        $resourceType = $result['resource_type'] ?? null;

        if (! is_string($publicId) || $publicId === ''
            || ! is_string($secureUrl) || $secureUrl === ''
            || ! is_string($resourceType) || $resourceType === '') {
            if (is_string($publicId) && $publicId !== '') {
                try {
                    $this->delete(
                        $publicId,
                        is_string($resourceType) && $resourceType !== '' ? $resourceType : (string) $options['resource_type'],
                        (string) $options['type'],
                    );
                } catch (Throwable) {
                    // The upload already failed; cleanup is best effort.
                }
            }

            throw new CloudinaryUploadException('The document storage provider returned an invalid response.');
        }

        $format = isset($result['format']) && is_string($result['format'])
            ? $result['format']
            : $extension;

        return new CloudinaryAsset(
            publicId: $publicId,
            assetId: isset($result['asset_id']) && is_string($result['asset_id']) ? $result['asset_id'] : null,
            secureUrl: $secureUrl,
            resourceType: $resourceType,
            format: $format,
            bytes: (int) ($result['bytes'] ?? $file->getSize() ?? 0),
            deliveryType: (string) ($options['type'] ?? 'private'),
            metadata: array_filter([
                'version' => $result['version'] ?? null,
                'etag' => $result['etag'] ?? null,
                'created_at' => $result['created_at'] ?? null,
                'type' => $result['type'] ?? ($options['type'] ?? null),
                'folder' => $folder,
            ], static fn (mixed $value): bool => $value !== null),
        );
    }

    public function downloadToTemp(string $publicId, string $format, string $resourceType): string
    {
        if ($publicId === '' || $format === '' || $resourceType === '') {
            throw new CloudinaryUploadException('The stored document reference is unavailable.');
        }

        $expiresAt = time() + max(60, (int) config('services.cloudinary.download_expiry_seconds', 900));
        $url = $this->client->privateDownloadUrl($publicId, $format, [
            'resource_type' => $resourceType,
            'type' => (string) config('services.cloudinary.document_delivery_type', 'private'),
            'attachment' => true,
            'expires_at' => $expiresAt,
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'docling-');

        if ($tempPath === false) {
            throw new CloudinaryUploadException('A temporary file could not be created.');
        }

        try {
            $response = Http::timeout(max(1, (int) config('docling.timeout', 240)))
                ->connectTimeout(max(1, (int) config('docling.connect_timeout', 5)))
                ->get($url)
                ->throw();

            $contents = $response->body();
            $maxBytes = max(1, (int) config('documents.max_size_kb', 2048)) * 1024;

            if ($contents === '' || strlen($contents) > $maxBytes) {
                throw new CloudinaryUploadException('The stored document could not be downloaded safely.');
            }

            if (file_put_contents($tempPath, $contents) === false) {
                throw new CloudinaryUploadException('The stored document could not be downloaded safely.');
            }
        } catch (CloudinaryUploadException $exception) {
            @unlink($tempPath);

            throw $exception;
        } catch (Throwable) {
            @unlink($tempPath);

            throw new CloudinaryUploadException('The stored document could not be downloaded.');
        }

        return $tempPath;
    }

    public function delete(string $publicId, string $resourceType, string $deliveryType): void
    {
        $this->client->destroy($publicId, [
            'resource_type' => $resourceType,
            'type' => $deliveryType,
            'invalidate' => true,
        ]);
    }
}
