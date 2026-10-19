<?php

namespace App\Services\Cloudinary;

use App\Contracts\Cloudinary\CloudinaryClient;
use Cloudinary;
use Cloudinary\Uploader;

class SdkCloudinaryClient implements CloudinaryClient
{
    public function __construct(Cloudinary $cloudinary)
    {
        // Resolving the SDK object applies the configured static credentials.
        unset($cloudinary);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function upload(string $filePath, array $options): array
    {
        return (array) Uploader::upload($filePath, $options);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function privateDownloadUrl(string $publicId, string $format, array $options): string
    {
        return (string) Cloudinary::private_download_url($publicId, $format, $options);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function destroy(string $publicId, array $options): array
    {
        return (array) Uploader::destroy($publicId, $options);
    }
}
