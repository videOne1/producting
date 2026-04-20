<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ProcessProductImportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $filePath,
        public int $tenantId,
    )
    {
    }

    public function handle(): void
    {
        $stream = Storage::readStream($this->filePath);

        if (!$stream) {
            throw new \RuntimeException('File not found in storage: ' . $this->filePath);
        }

        $headers = fgetcsv($stream, 0, ',');

        if ($headers === false) {
            fclose($stream);
            throw new \RuntimeException('CSV header row is missing.');
        }

        $headers = array_map('trim', $headers);

        while (($row = fgetcsv($stream, 0, ',')) !== false) {
            if (count(array_filter($row, fn ($value) => $value !== null && trim($value) !== '')) === 0) {
                continue;
            }

            $row = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);

            if (count($row) !== count($headers)) {
                continue;
            }

            $data = array_combine($headers, $row);

            if ($data === false) {
                continue;
            }

            Product::create([
                'tenant_id' => $this->tenantId,
                'name' => $data['name'] ?? '',
                'sku' => $data['sku'] ?? '',
                'price' => isset($data['price']) ? (float) $data['price'] : 0,
            ]);
        }

        fclose($stream);
    }
}
