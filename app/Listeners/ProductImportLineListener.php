<?php

namespace App\Listeners;

use App\Events\ProductImportLine;
use App\Models\Product;
use App\Models\ProductImportFailure;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProductImportLineListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ProductImportLine $event): void
    {
        $row = $event->csvRow;

        $existing = Product::where('tenant_id', $event->tenantid)->where('sku', (int) $row['sku'])->first();

        if($existing){
            $done = $existing->update($row);
        }else{
            $done = Product::create($row);
        }
        
        if(!$done){
            ProductImportFailure::create([
            'product_import_id' => $event->jobid,
            'row_number' => $event->rowNumber,
            'failure_type' => 'import',
            'row_data' => $row,
            'error_message' => "Failed to import product",
        ]);
        }
    }
}
