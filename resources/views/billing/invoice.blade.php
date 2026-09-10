@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto bg-white rounded shadow p-6">
        <div class="flex justify-between border-b pb-3 mb-4">
            <h2 class="text-xl font-semibold">Invoice #{{ $order->id }}</h2>
            <span class="text-sm text-slate-500">{{ $order->created_at->format('d M Y, H:i') }}</span>
        </div>

        <p class="text-sm"><strong>Customer:</strong> {{ $order->customer->name }} ({{ $order->customer->email }})</p>

        <table class="w-full text-sm mt-4">
            <thead class="bg-slate-100">
                <tr>
                    <th class="text-left px-2 py-1">Product</th>
                    <th class="text-center px-2 py-1">Qty</th>
                    <th class="text-right px-2 py-1">Price</th>
                    <th class="text-right px-2 py-1">Tax</th>
                    <th class="text-right px-2 py-1">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr class="border-b">
                        <td class="px-2 py-1">{{ $item->product->name }}</td>
                        <td class="text-center px-2 py-1">{{ $item->quantity }}</td>
                        <td class="text-right px-2 py-1">₹{{ number_format($item->price_per_unit, 2) }}</td>
                        <td class="text-right px-2 py-1">₹{{ number_format($item->tax, 2) }}</td>
                        <td class="text-right px-2 py-1">₹{{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4 flex justify-end">
            <div class="w-64 text-sm space-y-1">
                <div class="flex justify-between">
                    <span>Subtotal:</span><span>₹{{ number_format($order->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between"><span>Tax:</span><span>₹{{ number_format($order->tax, 2) }}</span></div>
                <div class="flex justify-between font-bold border-t pt-1">
                    <span>Grand Total:</span><span>₹{{ number_format($order->grand_total, 2) }}</span>
                </div>
                <div class="flex justify-between"><span>Amount
                        Given:</span><span>₹{{ number_format($order->amount_given ?? 0, 2) }}</span></div>
                <div class="flex justify-between font-semibold">
                    <span>Balance:</span><span>₹{{ number_format($order->balance_returned ?? 0, 2) }}</span>
                </div>
            </div>
        </div>

        @php
            $due = $order->grand_total - ($order->amount_given ?? 0);
        @endphp

        @if ($due > 0)
            <div class="mt-4 p-3 bg-rose-50 border border-rose-300 rounded">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold text-rose-800">⚠ Due Amount</div>
                        <div class="text-xs text-rose-600">
                            Customer has not paid the full amount for this order.
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xl font-bold text-rose-700">₹{{ number_format($due, 2) }}</div>
                        <div class="text-xs text-rose-600">Remaining</div>
                    </div>
                </div>
            </div>
        @elseif(($order->balance_returned ?? 0) > 0)
            <div class="mt-4 p-3 bg-emerald-50 border border-emerald-300 rounded">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold text-emerald-800">✓ Balance Returned</div>
                        <div class="text-xs text-emerald-600">Customer paid more than the total.</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xl font-bold text-emerald-700">₹{{ number_format($order->balance_returned, 2) }}
                        </div>
                        <div class="text-xs text-emerald-600">Returned</div>
                    </div>
                </div>
            </div>
        @endif

        @if (!empty($changeBreakdown))
            <div class="mt-3 text-xs text-slate-600">
                Change breakdown:
                {{ collect($changeBreakdown)->map(fn($c, $d) => "{$c}×{$d}")->implode(' + ') }}
            </div>
        @endif

        <div class="mt-6 flex justify-between">
            <a href="{{ route('billing.index') }}" class="text-blue-600 text-sm">← New Order</a>
            <button onclick="window.print()" class="bg-slate-700 text-white px-4 py-2 rounded text-sm">Print</button>
        </div>
    </div>
@endsection
