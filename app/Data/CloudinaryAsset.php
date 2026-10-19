<?php

namespace App\Data;

/**
 * Safe subset of a Cloudinary upload response.
 */
final readonly class CloudinaryAsset
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $publicId,
        public ?string $assetId,
        public string $secureUrl,
        public string $resourceType,
        public ?string $format,
        public int $bytes,
        public string $deliveryType = 'private',
        public array $metadata = [],
    ) {}
}
