<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessProductImportJob;
use App\Models\Product;
use App\Models\ProductImport as ModelsProductImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id', $request->user()?->tenant_id);

        if ($tenantId === null) {
            return response()->json([
                'message' => 'Authenticated user is not linked to a tenant.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|max:255', 
            'sku' => [
                'required',
                'max:100',
                Rule::unique('products', 'sku')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)
                ),
            ],
            'price' => 'required|numeric|min:0'
        ]);

        $product = Product::create([
            ...$validated,
            'tenant_id' => (int) $tenantId,
        ]);

        return response()->json([
            'id' => $product->id,
            'tenant_id' => $product->tenant_id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }

    public function image(Product $product, Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = (int) $request->attributes->get('tenant_id');

        if ($user === null || $tenantId === 0) {
            return response()->json([
                'message' => 'Authenticated user is not linked to a tenant.',
            ], 403);
        }

        if ((int) $user->tenant_id !== $tenantId) {
            return response()->json([
                'message' => 'Tenant access denied.',
            ], 403);
        }

        if ((int) $product->tenant_id !== $tenantId) {
            return response()->json([
                'message' => 'Product not found.',
            ], 404);
        }

        $image = $product->images()
            ->where('is_primary', true)
            ->first();

        if ($image === null) {
            return response()->json([
                'message' => 'Product image not found.',
            ], 404);
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('s3');

        if (! $disk->exists($image->path)) {
            return response()->json([
                'message' => 'Product image not found.',
            ], 404);
        }

        $cacheKey = sprintf(
            'tenant:%d:image:%d:%s',
            $tenantId,
            $image->id,
            optional($image->updated_at)->timestamp ?? 'no-ts'
        );

        $downloadUrl = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($disk, $image) {
            return $disk->temporaryUrl(
                $image->path,
                now()->addMinutes(10)
            );
        });

        return response()->json([
            'download_url' => $downloadUrl,
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id', $request->user()?->tenant_id);
        $user = $request->user();

        if ($tenantId === null) {
            return response()->json([
                'message' => 'Authenticated user is not linked to a tenant.',
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $path = $request->file('file')->store(sprintf('uploads/tenant-%d', $tenantId));

        $created = ModelsProductImport::query()->create([
            'file_path' => $path,
            'tenant_id' => $tenantId,
            'status' => 'pending',
            'uploaded_by' => $user?->id,
        ]);

        if (! $created) {
            return response()->json(['message' => 'Import failed'], 500);
        }

        ProcessProductImportJob::dispatch($created->id);

        return response()->json([
            'message' => 'Import started',
            'import_id' => $created->id,
            'status' => $created->status,
        ], 202);
    }
}
