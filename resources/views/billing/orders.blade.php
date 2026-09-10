@extends('layouts.app')

@section('content')
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">📋 Orders</h1>
                <p class="text-sm text-slate-500">All orders with customer and item details</p>
            </div>
            <a href="{{ route('billing.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                + New Order
            </a>
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <div class="p-3 bg-emerald-100 border border-emerald-400 text-emerald-800 rounded">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-3 bg-red-100 border border-red-400 text-red-800 rounded">
                {{ session('error') }}
            </div>
        @endif

        {{-- Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-white rounded shadow p-4 border-l-4 border-blue-500">
                <div class="text-xs text-slate-500 uppercase">Total Orders</div>
                <div class="text-2xl font-bold text-slate-800">{{ number_format($stats['total_orders']) }}</div>
            </div>
            <div class="bg-white rounded shadow p-4 border-l-4 border-emerald-500">
                <div class="text-xs text-slate-500 uppercase">Total Revenue</div>
                <div class="text-2xl font-bold text-emerald-600">₹{{ number_format($stats['total_revenue'], 2) }}</div>
            </div>
            <div class="bg-white rounded shadow p-4 border-l-4 border-amber-500">
                <div class="text-xs text-slate-500 uppercase">Total Tax</div>
                <div class="text-2xl font-bold text-amber-600">₹{{ number_format($stats['total_tax'], 2) }}</div>
            </div>
            <div class="bg-white rounded shadow p-4 border-l-4 border-rose-500">
                <div class="text-xs text-slate-500 uppercase">Pending Amount</div>
                <div class="text-2xl font-bold text-rose-600">₹{{ number_format($stats['total_pending'] ?? 0, 2) }}</div>
            </div>
            <div class="bg-white rounded shadow p-4 border-l-4 border-purple-500">
                <div class="text-xs text-slate-500 uppercase">Customers</div>
                <div class="text-2xl font-bold text-purple-600">{{ number_format($stats['total_customers']) }}</div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded shadow p-4">
            <form method="GET" action="{{ route('billing.orders.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Customer Email</label>
                    <input type="text" name="email" value="{{ request('email') }}" placeholder="Search by email"
                        class="w-full border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">From Date</label>
                    <input type="date" name="from" value="{{ request('from') }}"
                        class="w-full border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">To Date</label>
                    <input type="date" name="to" value="{{ request('to') }}"
                        class="w-full border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Payment Status</label>
                    <select name="payment_status" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="all" @selected(request('payment_status', 'all') === 'all')>All Orders</option>
                        <option value="pending" @selected(request('payment_status') === 'pending')>Has Pending Amount</option>
                        <option value="paid" @selected(request('payment_status') === 'paid')>Fully Paid</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded text-sm">
                        Filter
                    </button>
                    <a href="{{ route('billing.orders.index') }}"
                        class="text-slate-500 hover:text-slate-700 px-3 py-2 text-sm">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Orders Table --}}
        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-700 uppercase text-xs">
                    <tr>
                        <th class="text-left px-3 py-3">Order #</th>
                        <th class="text-left px-3 py-3">Date</th>
                        <th class="text-left px-3 py-3">Customer</th>
                        <th class="text-center px-3 py-3">Items</th>
                        <th class="text-right px-3 py-3">Subtotal</th>
                        <th class="text-right px-3 py-3">Tax</th>
                        <th class="text-right px-3 py-3">Grand Total</th>
                        <th class="text-right px-3 py-3">Amount Given</th>
                        <th class="text-right px-3 py-3">Pending</th>
                        <th class="text-center px-3 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $amountGiven = (float) ($order->amount_given ?? 0);
                            $grandTotal = (float) $order->grand_total;
                            $pending = round($grandTotal - $amountGiven, 2);
                            $isPending = $pending > 0;
                            $isCredit = $pending < 0;
                        @endphp

                        <tr class="border-t hover:bg-slate-50 order-row cursor-pointer
                               {{ $isPending ? 'bg-rose-50/40' : '' }}"
                            data-target="order-items-{{ $order->id }}">
                            <td class="px-3 py-3 font-semibold text-blue-600">#{{ $order->id }}</td>
                            <td class="px-3 py-3 text-slate-600">
                                {{ $order->created_at->format('d M Y') }}<br>
                                <span class="text-xs text-slate-400">{{ $order->created_at->format('H:i') }}</span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="font-medium text-slate-800">{{ $order->customer->name }}</div>
                                <div class="text-xs text-slate-500">{{ $order->customer->email }}</div>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span
                                    class="inline-flex items-center bg-slate-100 text-slate-700 text-xs font-medium px-2 py-1 rounded">
                                    {{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right text-slate-700">₹{{ number_format($order->subtotal, 2) }}</td>
                            <td class="px-3 py-3 text-right text-slate-700">₹{{ number_format($order->tax, 2) }}</td>
                            <td class="px-3 py-3 text-right font-bold text-emerald-600">
                                ₹{{ number_format($order->grand_total, 2) }}
                            </td>
                            <td class="px-3 py-3 text-right text-slate-700">₹{{ number_format($amountGiven, 2) }}</td>
                            <td class="px-3 py-3 text-right">
                                @if ($isPending)
                                    <span class="inline-flex flex-col items-end">
                                        <span class="font-bold text-rose-600">₹{{ number_format($pending, 2) }}</span>
                                        <span class="text-[10px] uppercase font-semibold text-rose-500">Pending</span>
                                    </span>
                                @elseif($isCredit)
                                    <span class="inline-flex flex-col items-end">
                                        <span class="font-bold text-blue-600">₹{{ number_format(abs($pending), 2) }}</span>
                                        <span class="text-[10px] uppercase font-semibold text-blue-500">Credit</span>
                                    </span>
                                @else
                                    <span class="inline-flex flex-col items-end">
                                        <span class="font-bold text-emerald-600">₹0.00</span>
                                        <span class="text-[10px] uppercase font-semibold text-emerald-500">Paid</span>
                                    </span>
                                @endif
                            </td>

                            {{-- ✅ NEW: Actions --}}
                            <td class="px-3 py-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('billing.invoice', $order) }}"
                                        class="text-xs text-slate-600 hover:text-slate-800 font-medium"
                                        onclick="event.stopPropagation()">
                                        View
                                    </a>
                                    <button type="button"
                                        class="btn-edit-order text-xs text-blue-600 hover:text-blue-800 font-medium"
                                        data-id="{{ $order->id }}" onclick="event.stopPropagation()">
                                        Edit
                                    </button>
                                    <button type="button"
                                        class="btn-delete-order text-xs text-rose-600 hover:text-rose-800 font-medium"
                                        data-id="{{ $order->id }}"
                                        data-label="Order #{{ $order->id }} — {{ $order->customer->name }}"
                                        onclick="event.stopPropagation()">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>

                        {{-- Expanded detail row --}}
                        <tr class="hidden bg-slate-50" id="order-items-{{ $order->id }}">
                            <td colspan="10" class="p-0">
                                <div class="p-4 border-l-4 border-blue-400">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                                        <div class="bg-white rounded p-3">
                                            <div class="text-xs text-slate-500 uppercase mb-1">Customer Details</div>
                                            <div class="text-sm font-medium">{{ $order->customer->name }}</div>
                                            <div class="text-xs text-slate-500">{{ $order->customer->email }}</div>
                                            <div class="text-xs text-slate-400 mt-1">
                                                Customer since: {{ $order->customer->created_at->format('d M Y') }}
                                            </div>
                                        </div>

                                        <div class="bg-white rounded p-3">
                                            <div class="text-xs text-slate-500 uppercase mb-1">Payment</div>
                                            <div class="flex justify-between text-sm">
                                                <span>Grand Total:</span>
                                                <span class="font-medium">₹{{ number_format($grandTotal, 2) }}</span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span>Amount Given:</span>
                                                <span class="font-medium">₹{{ number_format($amountGiven, 2) }}</span>
                                            </div>
                                            <div class="flex justify-between text-sm border-t mt-1 pt-1">
                                                <span>Balance Returned:</span>
                                                <span class="font-medium text-amber-600">
                                                    ₹{{ number_format($order->balance_returned ?? 0, 2) }}
                                                </span>
                                            </div>

                                            @if ($isPending)
                                                <div
                                                    class="flex justify-between text-sm mt-2 pt-2 border-t-2 border-rose-300">
                                                    <span class="font-semibold text-rose-700">Pending:</span>
                                                    <span
                                                        class="font-bold text-rose-700">₹{{ number_format($pending, 2) }}</span>
                                                </div>
                                            @elseif($isCredit)
                                                <div
                                                    class="flex justify-between text-sm mt-2 pt-2 border-t-2 border-blue-300">
                                                    <span class="font-semibold text-blue-700">Credit:</span>
                                                    <span
                                                        class="font-bold text-blue-700">₹{{ number_format(abs($pending), 2) }}</span>
                                                </div>
                                            @else
                                                <div
                                                    class="flex justify-between text-sm mt-2 pt-2 border-t-2 border-emerald-300">
                                                    <span class="font-semibold text-emerald-700">Status:</span>
                                                    <span class="font-bold text-emerald-700">✓ Fully Paid</span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="bg-white rounded p-3">
                                            <div class="text-xs text-slate-500 uppercase mb-1">Order Meta</div>
                                            <div class="text-xs text-slate-600">
                                                Order ID: <span class="font-mono">#{{ $order->id }}</span><br>
                                                Placed: {{ $order->created_at->format('d M Y, H:i:s') }}<br>
                                                Last updated: {{ $order->updated_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-white rounded overflow-hidden">
                                        <table class="w-full text-xs">
                                            <thead class="bg-slate-100 text-slate-600">
                                                <tr>
                                                    <th class="text-left px-3 py-2">Product</th>
                                                    <th class="text-left px-3 py-2">Code</th>
                                                    <th class="text-center px-3 py-2">Qty</th>
                                                    <th class="text-right px-3 py-2">Unit Price</th>
                                                    <th class="text-right px-3 py-2">Tax %</th>
                                                    <th class="text-right px-3 py-2">Subtotal</th>
                                                    <th class="text-right px-3 py-2">Tax</th>
                                                    <th class="text-right px-3 py-2">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($order->items as $item)
                                                    <tr class="border-t">
                                                        <td class="px-3 py-2 font-medium">{{ $item->product->name }}</td>
                                                        <td class="px-3 py-2 text-slate-500 font-mono">
                                                            {{ $item->product->unique_code }}</td>
                                                        <td class="px-3 py-2 text-center">{{ $item->quantity }}</td>
                                                        <td class="px-3 py-2 text-right">
                                                            ₹{{ number_format($item->price_per_unit, 2) }}</td>
                                                        <td class="px-3 py-2 text-right">
                                                            {{ number_format($item->tax_percentage, 2) }}%</td>
                                                        <td class="px-3 py-2 text-right">
                                                            ₹{{ number_format($item->subtotal, 2) }}</td>
                                                        <td class="px-3 py-2 text-right">
                                                            ₹{{ number_format($item->tax, 2) }}</td>
                                                        <td class="px-3 py-2 text-right font-semibold">
                                                            ₹{{ number_format($item->total, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="bg-slate-50 font-semibold">
                                                <tr>
                                                    <td colspan="7" class="text-right px-3 py-2">Grand Total:</td>
                                                    <td class="text-right px-3 py-2 text-emerald-600">
                                                        ₹{{ number_format($order->grand_total, 2) }}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-12 text-slate-400">
                                <div class="text-4xl mb-2">📭</div>
                                <div>No orders found.</div>
                                <a href="{{ route('billing.index') }}"
                                    class="inline-block mt-3 text-blue-600 hover:text-blue-800 text-sm">
                                    Create your first order →
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="flex justify-center">{{ $orders->links() }}</div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════
     ✅ EDIT ORDER MODAL
     ═══════════════════════════════════════════════════════════ --}}
    <div id="editOrderModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <form method="POST" id="editOrderForm" action="">
                @csrf
                @method('PUT')

                <div class="flex justify-between items-center px-5 py-3 border-b sticky top-0 bg-white">
                    <h3 class="font-semibold text-slate-800" id="editOrderTitle">✏️ Edit Order</h3>
                    <button type="button" onclick="closeEditOrderModal()"
                        class="text-slate-400 hover:text-slate-600 text-xl leading-none">×</button>
                </div>

                <div class="p-5 space-y-5">

                    {{-- Customer --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">
                                Customer Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="customer_email" id="eoEmail"
                                class="w-full border rounded px-3 py-2 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">
                                Customer Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="customer_name" id="eoName"
                                class="w-full border rounded px-3 py-2 text-sm" required>
                        </div>
                    </div>

                    {{-- Items --}}
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-xs font-medium text-slate-600">
                                Items <span class="text-red-500">*</span>
                            </label>
                            <button type="button" id="eoAddItem"
                                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                + Add Item
                            </button>
                        </div>

                        <div class="border rounded overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-100 text-slate-600 text-xs">
                                    <tr>
                                        <th class="text-left px-3 py-2">Product</th>
                                        <th class="text-center px-3 py-2 w-24">Qty</th>
                                        <th class="text-right px-3 py-2 w-28">Line Total</th>
                                        <th class="w-10"></th>
                                    </tr>
                                </thead>
                                <tbody id="eoItemsBody"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Totals + Amount --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-50 rounded p-3 text-sm space-y-1">
                            <div class="flex justify-between"><span>Subtotal:</span><span id="eoSubtotal">₹0.00</span>
                            </div>
                            <div class="flex justify-between"><span>Tax:</span><span id="eoTax">₹0.00</span></div>
                            <div class="flex justify-between font-bold text-base border-t pt-1">
                                <span>Grand Total:</span><span id="eoGrandTotal" class="text-emerald-600">₹0.00</span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-slate-600">
                                Amount Given <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" name="amount_given" id="eoAmountGiven"
                                class="w-full border rounded px-3 py-2 text-sm" required>
                            <div class="text-xs text-slate-500">
                                Balance: <span id="eoBalance" class="font-semibold">₹0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded p-3 text-xs text-amber-800">
                        ⚠️ <strong>Stock will be updated:</strong> old item quantities are returned to stock,
                        then the new quantities are deducted. If any new quantity exceeds available stock, the
                        update will be rejected.
                    </div>
                </div>

                <div class="flex justify-end gap-2 px-5 py-3 border-t bg-slate-50 sticky bottom-0">
                    <button type="button" onclick="closeEditOrderModal()"
                        class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
     ✅ DELETE ORDER MODAL
     ═══════════════════════════════════════════════════════════ --}}
    <div id="deleteOrderModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <form method="POST" id="deleteOrderForm" action="">
                @csrf
                @method('DELETE')

                <div class="p-5">
                    <div class="flex items-start gap-3">
                        <div class="bg-rose-100 rounded-full p-2">
                            <span class="text-rose-600 text-xl">⚠️</span>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-800">Delete Order?</h3>
                            <p class="text-sm text-slate-600 mt-1">
                                You are about to delete
                                <span class="font-semibold text-slate-800" id="deleteOrderLabel">this order</span>.
                            </p>
                            <p class="text-xs text-slate-500 mt-2">
                                <strong>Stock will be restored</strong> for all items in this order.
                                Customer balance will be recalculated automatically.
                                This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 px-5 py-3 border-t bg-slate-50">
                    <button type="button" onclick="closeDeleteOrderModal()"
                        class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded text-sm font-medium">
                        Yes, Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            // Products catalog for the edit modal dropdown
            const ALL_PRODUCTS = {!! json_encode(
                \App\Models\Product::orderBy('name')->get(['id', 'name', 'unique_code', 'price_per_unit', 'tax_percentage', 'stock_on_hand'])->map(function ($p) {
                        return [
                            'id' => $p->id,
                            'name' => $p->name,
                            'unique_code' => $p->unique_code,
                            'price' => (float) $p->price_per_unit,
                            'tax' => (float) $p->tax_percentage,
                            'stock' => $p->stock_on_hand,
                        ];
                    })->values(),
            ) !!};


            // ═══════════════════════════════════════════════════════════
            // Expand/collapse order row
            // ═══════════════════════════════════════════════════════════
            document.querySelectorAll('.order-row').forEach(row => {
                row.addEventListener('click', () => {
                    const targetId = row.dataset.target;
                    const targetRow = document.getElementById(targetId);
                    if (!targetRow) return;

                    document.querySelectorAll('tr[id^="order-items-"]').forEach(other => {
                        if (other.id !== targetId) other.classList.add('hidden');
                    });

                    targetRow.classList.toggle('hidden');
                });
            });

            // ═══════════════════════════════════════════════════════════
            // EDIT ORDER MODAL
            // ═══════════════════════════════════════════════════════════
            const editOrderModal = document.getElementById('editOrderModal');
            const editOrderForm = document.getElementById('editOrderForm');
            const eoItemsBody = document.getElementById('eoItemsBody');
            const eoAmountGiven = document.getElementById('eoAmountGiven');

            let eoRowCounter = 0;

            document.querySelectorAll('.btn-edit-order').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const id = btn.dataset.id;
                    try {
                        const res = await fetch(`/orders/${id}/edit-data`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) throw new Error('Failed to load order');
                        const {
                            order
                        } = await res.json();

                        document.getElementById('editOrderTitle').textContent =
                            `✏️ Edit Order #${order.id}`;
                        editOrderForm.action = `/orders/${order.id}`;

                        document.getElementById('eoEmail').value = order.customer_email;
                        document.getElementById('eoName').value = order.customer_name;
                        eoAmountGiven.value = order.amount_given.toFixed(2);

                        // Populate items
                        eoItemsBody.innerHTML = '';
                        eoRowCounter = 0;
                        order.items.forEach(item => addEditItemRow(item));

                        recalcEditTotals();

                        editOrderModal.classList.remove('hidden');

                    } catch (err) {
                        alert('Could not load order. Please try again.');
                    }
                });
            });

            function addEditItemRow(item = null) {
                const idx = eoRowCounter++;
                const tr = document.createElement('tr');
                tr.className = 'border-t eo-item-row';

                const options = ALL_PRODUCTS.map(p => {
                    const selected = item && item.product_id === p.id ? 'selected' : '';
                    const stock = p.stock; // ✅ p.stock (not p.stock_on_hand)
                    return `<option value="${p.id}"
                        data-price="${p.price}"           // ✅ p.price
                        data-tax="${p.tax}"               // ✅ p.tax
                        data-stock="${stock}" ${selected}>
                    ${p.name} (${p.unique_code}) — stock: ${stock}
                </option>`;
                }).join('');

                tr.innerHTML = `
                    <td class="px-3 py-2">
                        <select name="items[${idx}][product_id]" class="eo-product w-full border rounded px-2 py-1 text-sm" required>
                            <option value="">-- Select --</option>
                            ${options}
                        </select>
                    </td>
                    <td class="px-3 py-2">
                        <input type="number" min="1" name="items[${idx}][quantity]"
                            value="${item ? item.quantity : 1}"
                            class="eo-qty w-full border rounded px-2 py-1 text-sm text-center" required>
                    </td>
                    <td class="px-3 py-2 text-right eo-line-total font-medium">₹0.00</td>
                    <td class="px-3 py-2 text-center">
                        <button type="button" class="eo-remove text-rose-500 hover:text-rose-700">✕</button>
                    </td>
                `;
                eoItemsBody.appendChild(tr);
                recalcEditTotals();
            }

            document.getElementById('eoAddItem').addEventListener('click', () => addEditItemRow());

            eoItemsBody.addEventListener('input', recalcEditTotals);
            eoItemsBody.addEventListener('change', recalcEditTotals);
            eoItemsBody.addEventListener('click', (e) => {
                if (e.target.classList.contains('eo-remove')) {
                    if (eoItemsBody.querySelectorAll('.eo-item-row').length <= 1) {
                        alert('At least one item is required.');
                        return;
                    }
                    e.target.closest('tr').remove();
                    recalcEditTotals();
                }
            });

            eoAmountGiven.addEventListener('input', recalcEditTotals);

            function recalcEditTotals() {
                let subtotal = 0,
                    tax = 0;

                eoItemsBody.querySelectorAll('.eo-item-row').forEach(tr => {
                    const sel = tr.querySelector('.eo-product');
                    const qty = parseInt(tr.querySelector('.eo-qty').value || 0);
                    const opt = sel.selectedOptions[0];

                    if (!opt || !opt.dataset.price) {
                        tr.querySelector('.eo-line-total').textContent = '₹0.00';
                        return;
                    }

                    const price = parseFloat(opt.dataset.price);
                    const taxPct = parseFloat(opt.dataset.tax);

                    // Guard against NaN
                    if (isNaN(price) || isNaN(taxPct)) {
                        tr.querySelector('.eo-line-total').textContent = '₹0.00';
                        return;
                    }

                    const lineSub = price * qty;
                    const lineTax = lineSub * taxPct / 100;

                    subtotal += lineSub;
                    tax += lineTax;

                    tr.querySelector('.eo-line-total').textContent = '₹' + (lineSub + lineTax).toFixed(2);
                });

                const grand = subtotal + tax;
                document.getElementById('eoSubtotal').textContent = '₹' + subtotal.toFixed(2);
                document.getElementById('eoTax').textContent = '₹' + tax.toFixed(2);
                document.getElementById('eoGrandTotal').textContent = '₹' + grand.toFixed(2);

                const given = parseFloat(eoAmountGiven.value || 0);
                const balance = given - grand;
                const balEl = document.getElementById('eoBalance');
                balEl.textContent = '₹' + balance.toFixed(2);
                balEl.className = 'font-semibold ' +
                    (balance > 0 ? 'text-blue-600' : balance < 0 ? 'text-rose-600' : 'text-slate-700');
            }

            function closeEditOrderModal() {
                editOrderModal.classList.add('hidden');
            }

            editOrderModal.addEventListener('click', (e) => {
                if (e.target === editOrderModal) closeEditOrderModal();
            });

            // ═══════════════════════════════════════════════════════════
            // DELETE ORDER MODAL
            // ═══════════════════════════════════════════════════════════
            const deleteOrderModal = document.getElementById('deleteOrderModal');
            const deleteOrderForm = document.getElementById('deleteOrderForm');

            document.querySelectorAll('.btn-delete-order').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    const label = btn.dataset.label;
                    document.getElementById('deleteOrderLabel').textContent = label;
                    deleteOrderForm.action = `/orders/${id}`;
                    deleteOrderModal.classList.remove('hidden');
                });
            });

            function closeDeleteOrderModal() {
                deleteOrderModal.classList.add('hidden');
            }

            deleteOrderModal.addEventListener('click', (e) => {
                if (e.target === deleteOrderModal) closeDeleteOrderModal();
            });

            // Escape key closes modals
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    editOrderModal.classList.add('hidden');
                    deleteOrderModal.classList.add('hidden');
                }
            });
        </script>
    @endpush
@endsection
