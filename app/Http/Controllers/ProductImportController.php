<?php

namespace App\Http\Controllers;

use App\Models\ProductImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductImportController extends Controller
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
                'message' => 'Tenant not found.',
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
}
