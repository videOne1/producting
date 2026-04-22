<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessProductImportJob;
use App\Models\ProductImport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductImportController extends Controller
{
    use AuthorizesRequests;
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductImport $productImport, Request $request)
    {
        $tenantId = $request->attributes->get('tenant_id');

        if($tenantId === null) {
            return response()->json([
                'message' => 'Authenticated user is not linked to a tenant.',
            ], 403);
        }

        if($tenantId !== Auth::user()->tenant_id) {
            return response()->json([
                'message' => 'Tenant access denied.',
            ], 403);
        }

        if ((int) $tenantId !== (int) $productImport->tenant_id) {
            return response()->json([
                'message' => 'Product not found.',
            ], 404);
        }

        $product = ProductImport::query()->findOrFail($productImport->id);

        if($product === null) {
            return response()->json([
                'message' => 'Product import not found.',
            ], 404);
        }

        $returnData = [
            'id' => $product->id,
            'status' => $product->status,
            'processed_count' => $product->processed_count,
            'failed_count' => $product->failed_count,
            'error_message' => $product->error_message
        ];

        $failedRows = $productImport->failedRows()
        ->latest('row_number')
        ->limit(10)
        ->get([
            'row_number',
            'failure_type',
            'row_data',
            'error_message',
        ]);

        $returnData['failed_rows'] = $failedRows;

        return response()->json($returnData);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductImport $productImport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductImport $productImport)
    {
        //
    }

    public function retry(ProductImport $productImport, Request $request): JsonResponse
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

        if ((int) $productImport->tenant_id !== $tenantId) {
            return response()->json([
                'message' => 'Import not found.',
            ], 404);
        }

        Gate::authorize('admin-only');

        if ($productImport->status !== 'failed') {
            return response()->json([
                'message' => 'Import cannot be retried from its current status.',
            ], 400);
        }

        ProcessProductImportJob::dispatch($productImport->id, true);

        return response()->json([
            'message' => 'Product import is being retried.',
        ], 202);
    }
}
