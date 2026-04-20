<?php

namespace App\Jobs;

use App\Services\ProductImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessProductImportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $productImportId,
    )
    {
    }

    public function handle(ProductImportService $productImportService): void
    {
        $productImportService->processImportById($this->productImportId);
    }
}
