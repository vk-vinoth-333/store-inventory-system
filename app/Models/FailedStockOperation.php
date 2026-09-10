<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FailedStockOperation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity_requested',
        'stock_available',
        'error_message',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
