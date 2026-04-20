<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_import_id',
    'row_number',
    'failure_type',
    'row_data',
    'error_message',
])]
class ProductImportFailure extends Model
{
    protected function casts(): array
    {
        return [
            'row_data' => 'array',
        ];
    }

    public function productImport(): BelongsTo
    {
        return $this->belongsTo(ProductImport::class);
    }
}
