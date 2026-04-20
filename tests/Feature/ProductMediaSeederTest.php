<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Database\Seeders\ProductMediaSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductMediaSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_media_seeder_creates_tenant_scoped_products_and_images(): void
    {
        Storage::fake('local');

        $this->seed([
            UserSeeder::class,
            ProductMediaSeeder::class,
        ]);

        $product = Product::query()->with('images')->first();
        $image = ProductImage::query()->first();

        $this->assertNotNull($product);
        $this->assertNotNull($image);

        $uploader = User::query()->findOrFail($image->uploaded_by);

        $this->assertGreaterThan(0, $product->images->count());
        $this->assertSame($product->tenant_id, $uploader->tenant_id);
        $this->assertTrue($product->images->contains(fn (ProductImage $productImage) => $productImage->is_primary));
        Storage::disk('local')->assertExists($image->path);
    }
}
