<?php

namespace App\Contracts\Cloudinary;

interface CloudinaryClient
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function upload(string $filePath, array $options): array;

    /**
     * @param  array<string, mixed>  $options
     */
    public function privateDownloadUrl(string $publicId, string $format, array $options): string;

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function destroy(string $publicId, array $options): array;
}
