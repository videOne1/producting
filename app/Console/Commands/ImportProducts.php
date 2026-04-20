<?php

namespace App\Console\Commands;

use App\Services\ProductImportService;
use Illuminate\Console\Command;
use RuntimeException;

class ImportProducts extends Command
{
    protected $signature = 'products:import
        {import : The product_import record ID to process}
        {--force : Reprocess an import even if it is already marked as processing}';

    protected $description = 'Process a stored CSV product import';

    public function handle(ProductImportService $productImportService): int
    {
        $importId = (int) $this->argument('import');
        $force = (bool) $this->option('force');

        try {
            $import = $productImportService->processImportById($importId, $force);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($import->status === 'failed') {
            $this->error(sprintf(
                'Import [%d] failed: %s',
                $import->id,
                $import->error_message ?? 'Unknown import error.'
            ));

            return self::FAILURE;
        }

        $processedCount = (int) $import->processed_count;
        $failedCount = (int) $import->failed_count;

        if ($failedCount > 0) {
            $this->warn(sprintf(
                'Import [%d] completed with %d successful rows and %d failed rows.',
                $import->id,
                $processedCount,
                $failedCount
            ));
        } else {
            $this->info(sprintf(
                'Import [%d] completed successfully with %d rows.',
                $import->id,
                $processedCount
            ));
        }

        return self::SUCCESS;
    }
}
