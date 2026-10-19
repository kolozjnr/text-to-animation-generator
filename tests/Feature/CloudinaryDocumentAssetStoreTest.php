<?php

namespace Tests\Feature;

use App\Exceptions\CloudinaryUploadException;
use App\Models\User;
use App\Services\Cloudinary\CloudinaryDocumentAssetStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakeCloudinaryClient;
use Tests\TestCase;

class CloudinaryDocumentAssetStoreTest extends TestCase
{
    /** @var array<int, string> */
    private array $tempPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->tempPaths as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    public function test_upload_uses_a_private_raw_asset_with_a_generated_public_id(): void
    {
        $client = new FakeCloudinaryClient;
        $store = new CloudinaryDocumentAssetStore($client);
        $user = User::factory()->make();
        $user->id = 42;

        $asset = $store->upload($this->uploaded('secret-name.txt', 'Hello world'), $user);

        $this->assertCount(1, $client->uploads);
        $options = $client->uploads[0]['options'];

        $this->assertSame('raw', $options['resource_type']);
        $this->assertSame('private', $options['type']);
        $this->assertSame('source-documents/42', $options['folder']);
        $this->assertSame('X-Robots-Tag: noindex', $options['headers']);
        $this->assertFalse($options['use_filename']);
        $this->assertFalse($options['unique_filename']);
        $this->assertFalse($options['overwrite']);
        $this->assertArrayNotHasKey('access_control', $options);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.txt$/',
            $options['public_id'],
        );
        $this->assertStringNotContainsString('secret-name', $options['public_id']);
        $this->assertSame('private', $asset->deliveryType);
        $this->assertSame('raw', $asset->resourceType);
        $this->assertSame('txt', $asset->format);
        $this->assertSame('private', $asset->metadata['type']);
    }

    public function test_invalid_upload_responses_clean_up_the_uploaded_asset(): void
    {
        $client = new FakeCloudinaryClient;
        $client->uploadResponse = [
            'public_id' => 'source-documents/1/asset.txt',
            'resource_type' => 'raw',
            'type' => 'private',
        ];

        $store = new CloudinaryDocumentAssetStore($client);
        $user = User::factory()->make();
        $user->id = 7;

        try {
            $store->upload($this->uploaded('notes.txt', 'Hello world'), $user);
            $this->fail('Expected an invalid upload response to be rejected.');
        } catch (CloudinaryUploadException $exception) {
            $this->assertSame('The document storage provider returned an invalid response.', $exception->getMessage());
        }

        $this->assertCount(1, $client->uploads);
        $this->assertSame('source-documents/1/asset.txt', $client->destroys[0]['public_id']);
        $this->assertSame('raw', $client->destroys[0]['options']['resource_type']);
        $this->assertSame('private', $client->destroys[0]['options']['type']);
    }

    public function test_download_uses_a_short_lived_private_url(): void
    {
        config(['services.cloudinary.download_expiry_seconds' => 900]);

        Http::fake(['*' => Http::response('private document bytes', 200)]);

        $client = new FakeCloudinaryClient;
        $store = new CloudinaryDocumentAssetStore($client);

        $path = $store->downloadToTemp('source-documents/1/asset.txt', 'txt', 'raw');
        $this->tempPaths[] = $path;

        $this->assertSame('private document bytes', file_get_contents($path));
        $this->assertCount(1, $client->downloads);
        $this->assertSame('source-documents/1/asset.txt', $client->downloads[0]['public_id']);
        $this->assertSame('txt', $client->downloads[0]['format']);
        $this->assertSame('raw', $client->downloads[0]['options']['resource_type']);
        $this->assertSame('private', $client->downloads[0]['options']['type']);
        $this->assertTrue($client->downloads[0]['options']['attachment']);
        $this->assertIsInt($client->downloads[0]['options']['expires_at']);
    }

    public function test_download_failures_are_reported_without_exposing_the_signed_url(): void
    {
        Http::fake(['*' => Http::response('server error', 500)]);

        $client = new FakeCloudinaryClient;
        $client->downloadUrl = 'https://res.cloudinary.com/example/private?signature=secret';
        $store = new CloudinaryDocumentAssetStore($client);

        try {
            $store->downloadToTemp('source-documents/1/asset.txt', 'txt', 'raw');
            $this->fail('Expected a download failure.');
        } catch (CloudinaryUploadException $exception) {
            $this->assertSame('The stored document could not be downloaded.', $exception->getMessage());
            $this->assertStringNotContainsString('signature', $exception->getMessage());
        }
    }

    public function test_delete_removes_the_private_asset(): void
    {
        $client = new FakeCloudinaryClient;
        $store = new CloudinaryDocumentAssetStore($client);

        $store->delete('source-documents/1/asset.txt', 'raw', 'private');

        $this->assertSame('source-documents/1/asset.txt', $client->destroys[0]['public_id']);
        $this->assertSame('raw', $client->destroys[0]['options']['resource_type']);
        $this->assertSame('private', $client->destroys[0]['options']['type']);
        $this->assertTrue($client->destroys[0]['options']['invalidate']);
    }

    private function uploaded(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'cloudinary-test-');
        $this->assertNotFalse($path);
        file_put_contents($path, $content);
        $this->tempPaths[] = $path;

        return new UploadedFile($path, $name, 'text/plain', null, true);
    }
}
