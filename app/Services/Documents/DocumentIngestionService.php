<?php

namespace App\Services\Documents;

use App\Contracts\Documents\DocumentAssetStore;
use App\Enums\ExtractionStatus;
use App\Jobs\ExtractSourceDocument;
use App\Models\Document;
use App\Models\User;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DocumentIngestionService
{
    public function __construct(
        private readonly DocumentRepositoryInterface $documents,
        private readonly DocumentAssetStore $assets,
    ) {}

    /**
     * Upload a source document to private object storage and record its metadata.
     *
     * The original filename is retained only for display. The Cloudinary asset
     * uses a generated public ID, and extraction is queued after the database
     * transaction commits.
     */
    public function ingest(User $user, UploadedFile $file): Document
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $allowedExtensions = (array) config('documents.allowed_extensions', ['pdf', 'docx', 'txt']);

        if (! in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('Unsupported document extension.');
        }

        $asset = $this->assets->upload($file, $user);

        try {
            $document = DB::transaction(fn (): Document => $this->documents->create([
                'user_id' => $user->id,
                'original_name' => $this->safeOriginalName($file->getClientOriginalName(), $extension),
                'mime_type' => $this->mimeType($extension, $file),
                'size_bytes' => (int) ($asset->bytes ?: $file->getSize() ?: 0),
                'checksum' => $this->checksum($file),
                'storage_provider' => 'cloudinary',
                'storage_public_id' => $asset->publicId,
                'storage_asset_id' => $asset->assetId,
                'storage_url' => $asset->secureUrl,
                'storage_resource_type' => $asset->resourceType,
                'storage_format' => $asset->format ?: $extension,
                'storage_metadata' => $asset->metadata,
                'extraction_status' => ExtractionStatus::Pending,
                'failure_reason' => null,
            ]));
        } catch (Throwable $exception) {
            try {
                $this->assets->delete($asset->publicId, $asset->resourceType, $asset->deliveryType);
            } catch (Throwable) {
                Log::warning('Orphaned document asset cleanup failed.', [
                    'user_id' => $user->id,
                    'exception' => $exception::class,
                ]);
            }

            throw $exception;
        }

        ExtractSourceDocument::dispatch($document->id)->afterCommit();

        return $document;
    }

    private function safeOriginalName(string $name, string $fallbackExtension): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
        $name = trim(Str::limit($name, 255, ''));

        return $name !== '' ? $name : 'document.'.$fallbackExtension;
    }

    private function mimeType(string $extension, UploadedFile $file): string
    {
        $configured = (array) config('documents.allowed_mime_types', []);

        if (isset($configured[$extension]) && is_string($configured[$extension])) {
            return $configured[$extension];
        }

        return (string) ($file->getMimeType() ?: 'application/octet-stream');
    }

    private function checksum(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();

        if ($path === false) {
            return null;
        }

        $checksum = hash_file('sha256', $path);

        return $checksum === false ? null : $checksum;
    }
}
