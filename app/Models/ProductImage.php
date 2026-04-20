<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size',
    'sort_order',
    'is_primary',
    'uploaded_by',
])]
class ProductImage extends Model
{
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
