<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'file_path',
    'tenant_id',
    'status',
    'processed_count',
    'failed_count',
    'error_message',
    'uploaded_by',
])]
class ProductImport extends Model
{
    protected $table = 'product_import';

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function failedRows(): HasMany
    {
        return $this->hasMany(ProductImportFailure::class);
    }
}
