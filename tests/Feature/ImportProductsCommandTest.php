<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportProductsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_imports_products_and_updates_existing_skus(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $existingProduct = Product::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Old Mouse',
            'sku' => 'MOUSE-001',
            'price' => 49.99,
        ]);

        $path = 'uploads/tenant-'.$user->tenant_id.'/products.csv';

        Storage::disk('local')->put($path, implode(PHP_EOL, [
            'name,sku,price',
            'Gaming Mouse,MOUSE-001,59.99',
            'Mechanical Keyboard,KEY-001,89.50',
        ]));

        $import = ProductImport::query()->create([
            'file_path' => $path,
            'tenant_id' => $user->tenant_id,
            'uploaded_by' => $user->id,
        ]);

        $exitCode = Artisan::call('products:import', [
            'import' => $import->id,
        ]);

        $this->assertSame(0, $exitCode);

        $import->refresh();
        $existingProduct->refresh();

        $this->assertSame('completed', $import->status);
        $this->assertNotNull($import->started_at);
        $this->assertNotNull($import->finished_at);
        $this->assertSame(2, $import->processed_count);
        $this->assertSame(0, $import->failed_count);
        $this->assertNull($import->error_message);

        $this->assertSame('Gaming Mouse', $existingProduct->name);
        $this->assertSame('59.99', $existingProduct->price);
        $this->assertSame(2, Product::query()->where('tenant_id', $user->tenant_id)->count());
    }

    public function test_command_records_invalid_rows_without_aborting_the_import(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $path = 'uploads/tenant-'.$user->tenant_id.'/products-with-errors.csv';

        Storage::disk('local')->put($path, implode(PHP_EOL, [
            'name,sku,price',
            'Gaming Chair,CHAIR-001,199.95',
            'Broken Price,BAD-001,-10',
        ]));

        $import = ProductImport::query()->create([
            'file_path' => $path,
            'tenant_id' => $user->tenant_id,
            'uploaded_by' => $user->id,
        ]);

        $exitCode = Artisan::call('products:import', [
            'import' => $import->id,
        ]);

        $this->assertSame(0, $exitCode);

        $import->refresh();

        $this->assertSame('completed', $import->status);
        $this->assertNotNull($import->started_at);
        $this->assertNotNull($import->finished_at);
        $this->assertSame(1, $import->processed_count);
        $this->assertSame(1, $import->failed_count);
        $this->assertNull($import->error_message);
        $this->assertDatabaseHas('product_import_failures', [
            'product_import_id' => $import->id,
            'row_number' => 3,
            'failure_type' => 'validation',
        ]);
        $this->assertDatabaseHas('products', [
            'tenant_id' => $user->tenant_id,
            'sku' => 'CHAIR-001',
            'name' => 'Gaming Chair',
        ]);
        $this->assertDatabaseMissing('products', [
            'tenant_id' => $user->tenant_id,
            'sku' => 'BAD-001',
        ]);
    }

    public function test_command_marks_import_as_failed_when_required_headers_are_missing(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $path = 'uploads/tenant-'.$user->tenant_id.'/products-missing-price.csv';

        Storage::disk('local')->put($path, implode(PHP_EOL, [
            'name,sku',
            'Gaming Chair,CHAIR-001',
        ]));

        $import = ProductImport::query()->create([
            'file_path' => $path,
            'tenant_id' => $user->tenant_id,
            'uploaded_by' => $user->id,
        ]);

        $exitCode = Artisan::call('products:import', [
            'import' => $import->id,
        ]);

        $this->assertSame(1, $exitCode);

        $import->refresh();

        $this->assertSame('failed', $import->status);
        $this->assertNotNull($import->started_at);
        $this->assertNotNull($import->finished_at);
        $this->assertSame(0, $import->processed_count);
        $this->assertSame(0, $import->failed_count);
        $this->assertStringContainsString('price', (string) $import->error_message);
    }
}
