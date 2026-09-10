<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unique_code',
        'price_per_unit',
        'tax_percentage',
        'stock_on_hand',
    ];

    protected $casts = [
        'price_per_unit' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'stock_on_hand' => 'integer',
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function hasStock(int $quantity): bool
    {
        return $this->stock_on_hand >= $quantity;
    }

    public function scopeLowStock($query, int $threshold = 10)
    {
        return $query->where('stock_on_hand', '<', $threshold)
            ->orderBy('stock_on_hand');
    }
}
