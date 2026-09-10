<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Jobs\SendOrderConfirmation;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\FailedStockOperation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BillingService
{
    public function createOrder(array $data): Order
    {
        try {
            $order = DB::transaction(function () use ($data) {
                $customer = Customer::findOrCreateByEmail(
                    $data['customer_email'],
                    $data['customer_name']
                );

                $order = Order::create([
                    'customer_id' => $customer->id,
                    'subtotal' => 0,
                    'tax' => 0,
                    'grand_total' => 0,
                    'amount_given' => $data['amount_given'] ?? null,
                    'balance_returned' => null,
                ]);

                $productIds = collect($data['items'])->pluck('product_id')->unique()->all();
                $products = Product::whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($data['items'] as $item) {
                    $product = $products[$item['product_id']] ?? null;

                    if (!$product) {
                        throw new \RuntimeException("Product ID {$item['product_id']} not found.");
                    }

                    if (!$product->hasStock($item['quantity'])) {
                        FailedStockOperation::create([
                            'product_id' => $product->id,
                            'quantity_requested' => $item['quantity'],
                            'stock_available' => $product->stock_on_hand,
                            'error_message' => "Insufficient stock for {$product->name}",
                        ]);

                        throw new InsufficientStockException(
                            "Insufficient stock for {$product->name}. " .
                                "Available: {$product->stock_on_hand}, Requested: {$item['quantity']}"
                        );
                    }

                    $orderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'price_per_unit' => $product->price_per_unit,
                        'tax_percentage' => $product->tax_percentage,
                        'subtotal' => 0,
                        'tax' => 0,
                        'total' => 0,
                    ]);

                    $orderItem->refreshItemTotals();
                    $product->decrement('stock_on_hand', $item['quantity']);
                }

                $order->refreshTotals();

                if (isset($data['amount_given'])) {
                    $given = (float) $data['amount_given'];
                    $total = (float) $order->grand_total;

                    $order->update([
                        'balance_returned' => max(0, round($given - $total, 2)),
                    ]);
                }

                return $order->load(['customer', 'items.product']);
            });

            SendOrderConfirmation::dispatch($order);
            return $order;
        } catch (InsufficientStockException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('BillingService::createOrder failed', [
                'email' => $data['customer_email'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }


    public function recordPayment(Customer $customer, array $data): CustomerPayment
    {
        try {
            return DB::transaction(function () use ($customer, $data) {
                $payment = CustomerPayment::create([
                    'customer_id' => $customer->id,
                    'order_id' => $data['order_id'] ?? null,
                    'amount' => round((float) $data['amount'], 2),
                    'type' => $data['type'] ?? 'due_payment',
                    'notes' => $data['notes'] ?? null,
                    'recorded_by' => $data['recorded_by'] ?? null,
                ]);

                Log::info('Customer payment recorded', [
                    'customer_id' => $customer->id,
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'type' => $payment->type,
                    'new_balance' => $customer->fresh()->balance,
                ]);

                return $payment;
            });
        } catch (Throwable $e) {
            Log::error('recordPayment failed', [
                'customer_id' => $customer->id,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }


    public function updateOrder(Order $order, array $data): Order
    {
        try {
            $updated = DB::transaction(function () use ($order, $data) {
                $order = Order::with(['items.product', 'customer'])
                    ->lockForUpdate()
                    ->findOrFail($order->id);

                $oldItemCount = $order->items->count();
                $oldGrandTotal = (float) $order->grand_total;
                $oldAmountGiven = (float) ($order->amount_given ?? 0);

                $oldProductIds = $order->items->pluck('product_id')->unique()->all();
                $oldProducts = Product::whereIn('id', $oldProductIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($order->items as $oldItem) {
                    $product = $oldProducts[$oldItem->product_id] ?? null;
                    if ($product) {
                        $product->increment('stock_on_hand', $oldItem->quantity);
                    }
                }

                $order->items()->delete();

                if ($order->customer->email !== strtolower(trim($data['customer_email']))) {
                    $newCustomer = Customer::findOrCreateByEmail(
                        $data['customer_email'],
                        $data['customer_name']
                    );
                    $order->customer_id = $newCustomer->id;
                    $order->save();
                } else {
                    $order->customer->update(['name' => $data['customer_name']]);
                }

                $this->applyItemsToOrder($order, $data['items']);

                $order->refreshTotals();

                if (isset($data['amount_given'])) {
                    $newAmountGiven = (float) $data['amount_given'];

                    $order->update([
                        'amount_given' => $newAmountGiven,
                        'balance_returned' => max(0, round($newAmountGiven - $order->grand_total, 2)),
                    ]);

                }

                return $order->fresh(['customer', 'items.product']);
            });

            Log::info('Order updated', [
                'order_id' => $updated->id,
                'old_total' => $oldGrandTotal ?? null,
                'new_total' => $updated->grand_total,
                'old_items' => $oldItemCount ?? null,
                'new_items' => $updated->items->count(),
            ]);

            return $updated;

        } catch (InsufficientStockException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('BillingService::updateOrder failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

   
    public function deleteOrder(Order $order): void
    {
        try {
            DB::transaction(function () use ($order) {
                $order = Order::with('items')
                    ->lockForUpdate()
                    ->findOrFail($order->id);

                $productIds = $order->items->pluck('product_id')->unique()->all();
                $products = Product::whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($order->items as $item) {
                    $product = $products[$item->product_id] ?? null;
                    if ($product) {
                        $product->increment('stock_on_hand', $item->quantity);
                    }
                }

                $order->items()->delete();
                $order->delete();
            });

            Log::info('Order deleted', [
                'order_id' => $order->id,
            ]);

        } catch (Throwable $e) {
            Log::error('BillingService::deleteOrder failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

   
    private function applyItemsToOrder(Order $order, array $items): void
    {
        $productIds = collect($items)->pluck('product_id')->unique()->all();

        $products = Product::whereIn('id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $product = $products[$item['product_id']] ?? null;

            if (!$product) {
                throw new \RuntimeException("Product ID {$item['product_id']} not found.");
            }

            if (!$product->hasStock($item['quantity'])) {
                FailedStockOperation::create([
                    'product_id' => $product->id,
                    'quantity_requested' => $item['quantity'],
                    'stock_available' => $product->stock_on_hand,
                    'error_message' => "Insufficient stock for {$product->name}",
                ]);

                throw new InsufficientStockException(
                    "Insufficient stock for {$product->name}. " .
                    "Available: {$product->stock_on_hand}, Requested: {$item['quantity']}"
                );
            }

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price_per_unit' => $product->price_per_unit,
                'tax_percentage' => $product->tax_percentage,
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
            ]);

            $orderItem->refreshItemTotals();
            $product->decrement('stock_on_hand', $item['quantity']);
        }
    }
}
