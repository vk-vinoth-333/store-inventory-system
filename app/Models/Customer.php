<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public static function findOrCreateByEmail(string $email, string $name): self
    {
        return static::firstOrCreate(
            ['email' => $email],
            ['name' => $name]
        );
    }

    public function getTotalBilledAttribute(): float
    {
        return (float) $this->orders()->sum('grand_total');
    }

    public function getTotalPaidAtCounterAttribute(): float
    {
        return (float) $this->orders()->sum('amount_given');
    }

    public function getNetManualPaymentsAttribute(): float
    {
        $duePaid = (float) $this->payments()->where('type', 'due_payment')->sum('amount');
        $credits = (float) $this->payments()->where('type', 'credit')->sum('amount');
        $refunds = (float) $this->payments()->where('type', 'refund')->sum('amount');

        return $duePaid + $credits - $refunds;
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->total_paid_at_counter + $this->total_manual_payments;
    }

    public function getBalanceAttribute(): float
    {
        return round($this->total_billed - $this->total_paid, 2);
    }

    public function getDueAmountAttribute(): float
    {
        return max(0, $this->balance);
    }

    public function getCreditAmountAttribute(): float
    {
        return max(0, -$this->balance);
    }

    public function getHasDueAttribute(): bool
    {
        return $this->due_amount > 0;
    }
}
