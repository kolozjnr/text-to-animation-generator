<?php

namespace Tests\Feature;

use App\Contracts\Documents\DocumentAssetStore;
use App\Data\CloudinaryAsset;
use App\Models\User;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Services\Documents\DocumentIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use RuntimeException;
use Tests\Support\FakeDocumentAssetStore;
use Tests\TestCase;

class DocumentIngestionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cloudinary_asset_is_deleted_when_database_persistence_fails(): void
    {
        Queue::fake();

        $assets = new FakeDocumentAssetStore;
        $this->app->instance(DocumentAssetStore::class, $assets);

        $this->mock(DocumentRepositoryInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('create')
                ->once()
                ->andThrow(new RuntimeException('database unavailable'));
        });

        $service = $this->app->make(DocumentIngestionService::class);

        try {
            $service->ingest(
                User::factory()->create(),
                UploadedFile::fake()->createWithContent('notes.txt', 'Hello world'),
            );

            $this->fail('Expected the database failure to be rethrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('database unavailable', $exception->getMessage());
        }

        $this->assertInstanceOf(CloudinaryAsset::class, $assets->lastAsset);
        $this->assertCount(1, $assets->deleted);
        $this->assertSame($assets->lastAsset->publicId, $assets->deleted[0]['public_id']);
        $this->assertSame('raw', $assets->deleted[0]['resource_type']);
        $this->assertSame('private', $assets->deleted[0]['delivery_type']);
        $this->assertDatabaseCount('documents', 0);
    }
}
