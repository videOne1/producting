<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductMediaSeeder extends Seeder
{
    /**
     * Small valid PNG used as a placeholder so seeded image paths point to real files.
     */
    private const PLACEHOLDER_PNG_BASE64 =
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wn8nRsAAAAASUVORK5CYII=';

    /**
     * Demo catalog for tenant-scoped product media.
     *
     * @var array<int, array{name: string, sku_suffix: string, price: string, image_count: int}>
     */
    private const CATALOG = [
        [
            'name' => 'Wireless Mouse',
            'sku_suffix' => 'WIRELESS-MOUSE',
            'price' => '29.99',
            'image_count' => 3,
        ],
        [
            'name' => 'Mechanical Keyboard',
            'sku_suffix' => 'MECHANICAL-KEYBOARD',
            'price' => '89.50',
            'image_count' => 4,
        ],
        [
            'name' => 'USB-C Dock',
            'sku_suffix' => 'USB-C-DOCK',
            'price' => '119.00',
            'image_count' => 2,
        ],
    ];

    /**
     * Seed tenant-aware products with private image metadata and placeholder files.
     */
    public function run(): void
    {
        $users = User::query()
            ->whereNotNull('tenant_id')
            ->orderBy('id')
            ->take(12)
            ->get(['id', 'tenant_id']);

        if ($users->isEmpty()) {
            return;
        }

        $disk = Storage::disk('local');
        $placeholderImage = base64_decode(self::PLACEHOLDER_PNG_BASE64, true);

        if ($placeholderImage === false) {
            throw new \RuntimeException('Failed to decode seeded product placeholder image.');
        }

        foreach ($users as $user) {
            foreach (self::CATALOG as $catalogItem) {
                $product = Product::query()->updateOrCreate(
                    [
                        'tenant_id' => $user->tenant_id,
                        'sku' => sprintf('DEMO-%d-%s', $user->tenant_id, $catalogItem['sku_suffix']),
                    ],
                    [
                        'name' => $catalogItem['name'],
                        'price' => $catalogItem['price'],
                    ],
                );

                $directory = sprintf('products/tenant-%d/product-%d', $user->tenant_id, $product->id);

                $disk->deleteDirectory($directory);
                $product->images()->delete();

                for ($imageNumber = 1; $imageNumber <= $catalogItem['image_count']; $imageNumber++) {
                    $path = sprintf('%s/image-%02d.png', $directory, $imageNumber);

                    $disk->put($path, $placeholderImage);

                    $product->images()->create([
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => sprintf(
                            '%s-%02d.png',
                            Str::slug($product->name),
                            $imageNumber,
                        ),
                        'mime_type' => 'image/png',
                        'size' => strlen($placeholderImage),
                        'sort_order' => $imageNumber - 1,
                        'is_primary' => $imageNumber === 1,
                        'uploaded_by' => $user->id,
                    ]);
                }
            }
        }
    }
}
