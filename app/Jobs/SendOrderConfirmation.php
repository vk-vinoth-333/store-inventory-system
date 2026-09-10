<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function handle(): void
    {
        $this->order->loadMissing(['customer', 'items.product']);

        Log::info('[OrderConfirmation] Email sent', [
            'order_id' => $this->order->id,
            'to' => $this->order->customer->email,
            'customer' => $this->order->customer->name,
            'grand_total' => $this->order->grand_total,
            'item_count' => $this->order->items->count(),
        ]);
    }
}
