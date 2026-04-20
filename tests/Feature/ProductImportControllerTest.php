<?php

namespace Tests\Feature;

use App\Jobs\ProcessProductImportJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductImportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_endpoint_creates_an_import_and_dispatches_processing(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/imports/products', [
            'file' => UploadedFile::fake()->createWithContent('products.csv', implode(PHP_EOL, [
                'name,sku,price',
                'Gaming Mouse,MOUSE-001,59.99',
            ])),
        ]);

        $response
            ->assertAccepted()
            ->assertJsonPath('status', 'pending');

        $importId = $response->json('import_id');

        $this->assertNotNull($importId);
        $this->assertDatabaseHas('product_import', [
            'id' => $importId,
            'tenant_id' => $user->tenant_id,
            'status' => 'pending',
        ]);

        Queue::assertPushed(ProcessProductImportJob::class, function (ProcessProductImportJob $job) use ($importId): bool {
            return $job->productImportId === $importId;
        });
    }

    public function test_duplicate_api_prefixed_import_route_is_not_registered(): void
    {
        $response = $this->postJson('/api/api/imports/products');

        $response->assertNotFound();
    }
}
