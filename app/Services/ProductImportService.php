<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImport;
use App\Models\ProductImportFailure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class ProductImportService
{
    public function processImportById(int $importId, bool $force = false): ProductImport
    {
        $import = ProductImport::query()->find($importId);

        if ($import === null) {
            throw new RuntimeException(sprintf('Product import [%d] was not found.', $importId));
        }

        return $this->processImport($import, $force);
    }

    public function processImport(ProductImport $import, bool $force = false): ProductImport
    {
        if ($import->status === 'processing' && ! $force) {
            throw new RuntimeException(sprintf('Product import [%d] is already being processed.', $import->id));
        }

        $this->markAsProcessing($import);

        $stream = Storage::readStream($import->file_path);

        if (! is_resource($stream)) {
            return $this->failImport($import, sprintf(
                'Unable to open the CSV file at [%s].',
                $import->file_path
            ));
        }

        $processedCount = 0;
        $failedCount = 0;

        try {
            $headers = $this->readHeaders($stream);

            if ($headers === null) {
                return $this->failImport($import, 'CSV header row is missing or empty.');
            }

            $missingHeaders = array_values(array_diff(['name', 'sku', 'price'], $headers));

            if ($missingHeaders !== []) {
                return $this->failImport($import, sprintf(
                    'CSV is missing required columns: %s.',
                    implode(', ', $missingHeaders)
                ));
            }

            $rowNumber = 1;

            while (($row = fgetcsv($stream, 0, ',')) !== false) {
                $rowNumber++;

                if ($this->rowIsBlank($row)) {
                    continue;
                }

                if (count($row) !== count($headers)) {
                    $failedCount++;

                    $this->recordFailure(
                        $import,
                        $rowNumber,
                        'format',
                        $row,
                        'Column count does not match the CSV header.'
                    );

                    continue;
                }

                $row = array_map(
                    fn ($value) => is_string($value) ? trim($value) : $value,
                    $row
                );

                $data = array_combine($headers, $row);

                if ($data === false) {
                    $failedCount++;

                    $this->recordFailure(
                        $import,
                        $rowNumber,
                        'format',
                        $row,
                        'Unable to combine the row with the CSV header.'
                    );

                    continue;
                }

                $payload = [
                    'tenant_id' => (int) $import->tenant_id,
                    'name' => $data['name'] ?? null,
                    'sku' => $data['sku'] ?? null,
                    'price' => $data['price'] ?? null,
                ];

                $validator = Validator::make($payload, [
                    'tenant_id' => ['required', 'integer'],
                    'name' => ['required', 'string', 'max:255'],
                    'sku' => ['required', 'string', 'max:100'],
                    'price' => ['required', 'numeric', 'min:0'],
                ]);

                if ($validator->fails()) {
                    $failedCount++;

                    $this->recordFailure(
                        $import,
                        $rowNumber,
                        'validation',
                        $data,
                        $validator->errors()->first()
                    );

                    continue;
                }

                $validated = $validator->validated();
                $validated['price'] = round((float) $validated['price'], 2);

                try {
                    $product = Product::query()->firstOrNew([
                        'tenant_id' => $validated['tenant_id'],
                        'sku' => $validated['sku'],
                    ]);

                    $product->fill($validated)->save();
                    $processedCount++;
                } catch (Throwable $exception) {
                    report($exception);

                    $failedCount++;

                    $this->recordFailure(
                        $import,
                        $rowNumber,
                        'database',
                        $data,
                        $exception->getMessage()
                    );
                }
            }
        } catch (Throwable $exception) {
            report($exception);

            return $this->failImport(
                $import,
                $exception->getMessage(),
                $processedCount,
                $failedCount
            );
        } finally {
            fclose($stream);
        }

        $import->forceFill([
            'status' => 'completed',
            'finished_at' => now(),
            'processed_count' => $processedCount,
            'failed_count' => $failedCount,
            'error_message' => null,
        ])->save();

        return $import->fresh();
    }

    private function readHeaders($stream): ?array
    {
        $headers = fgetcsv($stream, 0, ',');

        if ($headers === false) {
            return null;
        }

        $headers = array_map(
            fn ($header) => is_string($header) ? strtolower(trim($header)) : '',
            $headers
        );

        return count(array_filter($headers, fn (string $header) => $header !== '')) === 0
            ? null
            : $headers;
    }

    private function rowIsBlank(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function recordFailure(
        ProductImport $import,
        int $rowNumber,
        string $failureType,
        array $rowData,
        string $errorMessage
    ): void {
        ProductImportFailure::query()->create([
            'product_import_id' => $import->id,
            'row_number' => $rowNumber,
            'failure_type' => $failureType,
            'row_data' => $rowData,
            'error_message' => $errorMessage,
        ]);
    }

    private function markAsProcessing(ProductImport $import): void
    {
        DB::transaction(function () use ($import): void {
            $import->failedRows()->delete();

            $import->forceFill([
                'status' => 'processing',
                'started_at' => now(),
                'finished_at' => null,
                'processed_count' => 0,
                'failed_count' => 0,
                'error_message' => null,
            ])->save();
        });
    }

    private function failImport(
        ProductImport $import,
        string $message,
        int $processedCount = 0,
        int $failedCount = 0
    ): ProductImport {
        $import->forceFill([
            'status' => 'failed',
            'finished_at' => now(),
            'processed_count' => $processedCount,
            'failed_count' => $failedCount,
            'error_message' => $message,
        ])->save();

        return $import->fresh();
    }
}
