<?php

namespace Tests\Support;

use App\Contracts\Cloudinary\CloudinaryClient;

class FakeCloudinaryClient implements CloudinaryClient
{
    /** @var array<int, array{path: string, options: array<string, mixed>}> */
    public array $uploads = [];

    /** @var array<int, array{public_id: string, format: string, options: array<string, mixed>}> */
    public array $downloads = [];

    /** @var array<int, array{public_id: string, options: array<string, mixed>}> */
    public array $destroys = [];

    /** @var array<string, mixed> */
    public array $uploadResponse = [
        'public_id' => 'source-documents/1/asset.txt',
        'asset_id' => 'asset-id',
        'secure_url' => 'https://res.cloudinary.com/example/raw/private/source-documents/1/asset.txt',
        'resource_type' => 'raw',
        'format' => 'txt',
        'bytes' => 11,
        'version' => 123,
        'etag' => 'etag-value',
        'created_at' => '2026-10-19T12:00:00Z',
        'type' => 'private',
    ];

    public string $downloadUrl = 'https://example.test/private-download';

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function upload(string $filePath, array $options): array
    {
        $this->uploads[] = ['path' => $filePath, 'options' => $options];

        return $this->uploadResponse;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function privateDownloadUrl(string $publicId, string $format, array $options): string
    {
        $this->downloads[] = [
            'public_id' => $publicId,
            'format' => $format,
            'options' => $options,
        ];

        return $this->downloadUrl;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function destroy(string $publicId, array $options): array
    {
        $this->destroys[] = ['public_id' => $publicId, 'options' => $options];

        return ['result' => 'ok'];
    }
}
