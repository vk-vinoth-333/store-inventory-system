<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'subtotal',
        'tax',
        'grand_total',
        'amount_given',
        'balance_returned',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'amount_given' => 'decimal:2',
        'balance_returned' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function refreshTotals(): void
    {
        $subtotal = $this->items()->sum('subtotal');
        $tax = $this->items()->sum('tax');
        $this->update([
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'grand_total' => round($subtotal + $tax, 2),
        ]);
    }
}
