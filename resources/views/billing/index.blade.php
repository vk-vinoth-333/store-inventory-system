@extends('layouts.app')

@section('content')
    <form method="POST" action="{{ route('billing.generate') }}" id="billingForm">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">

                <section>
                    <h2 class="font-semibold text-slate-700 mb-2">Customer</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Email</label>
                            <input type="email" name="customer_email" id="customerEmail" value="{{ old('customer_email') }}"
                                placeholder="e.g. thomas@example.com" class="w-full border rounded px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Name</label>
                            <input type="text" name="customer_name" id="customerName" value="{{ old('customer_name') }}"
                                placeholder="auto-filled if email exists" class="w-full border rounded px-3 py-2" required>
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="font-semibold text-slate-700 mb-2">Products</h2>
                    <div class="bg-white rounded border">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-100 text-slate-700">
                                <tr>
                                    <th class="text-left px-3 py-2">Product</th>
                                    <th class="text-center px-3 py-2 w-20">Qty</th>
                                    <th class="text-right px-3 py-2 w-24">Price</th>
                                    <th class="text-right px-3 py-2 w-28">Line Total</th>
                                    <th class="w-10"></th>
                                </tr>
                            </thead>
                            <tbody id="productRows"></tbody>
                        </table>
                    </div>
                    <button type="button" id="addProduct"
                        class="mt-3 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                        + Add Product
                    </button>
                </section>

                <section class="bg-white rounded border p-4">
                    <h2 class="font-semibold text-slate-700 mb-3">Payment</h2>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="flex justify-between"><span>Subtotal</span><span id="subtotalLabel">₹0.00</span></div>
                        <div class="flex justify-between"><span>Tax</span><span id="taxLabel">₹0.00</span></div>
                        <div class="flex justify-between font-semibold text-base">
                            <span>Grand Total</span><span id="grandTotalLabel">₹0.00</span>
                        </div>
                    </div>
                    <hr class="my-3">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Amount Given by Customer</label>
                    <input type="number" step="0.01" min="0" name="amount_given" id="amountGiven"
                        class="w-full border rounded px-3 py-2" placeholder="₹250" required>
                    <div class="flex justify-between mt-3 font-medium">
                        <span>Balance to Return:</span>
                        <span id="balanceLabel">₹0.00</span>
                        <span id="denomLabel" class="text-slate-500 text-xs"></span>
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <div class="bg-amber-50 border border-amber-300 rounded p-4">
                    <h3 class="font-semibold text-amber-800 mb-2">⚠ Low Stock Alert</h3>
                    <ul class="text-sm text-amber-900 space-y-1">
                        @forelse($lowStockProducts as $p)
                            <li>• {{ $p->name }} — {{ $p->stock_on_hand }} units left</li>
                        @empty
                            <li>All products sufficiently stocked.</li>
                        @endforelse
                    </ul>
                </div>

                <button type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 rounded">
                    Generate Bill
                </button>
                <p class="text-xs text-slate-500 mt-2">
                    → shows bill on page + emails PDF to customer
                </p>
            </div>
        </div>
    </form>

    <script>
        const products = {!! json_encode(
            $products->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'price' => (float) $p->price_per_unit,
                        'tax' => (float) $p->tax_percentage,
                        'stock' => $p->stock_on_hand,
                    ];
                })->values(),
        ) !!};


        let rowIndex = 0;
        const productRows = document.getElementById('productRows');

        function productOptions() {
            return products.map(p =>
                `<option value="${p.id}" data-price="${p.price}" data-tax="${p.tax}" data-stock="${p.stock}">
                    ${p.name} (stock: ${p.stock})
                </option>`
            ).join('');
        }

        function addRow() {
            const tr = document.createElement('tr');
            tr.className = 'border-t';
            tr.innerHTML = `
                <td class="px-2 py-1">
                    <select name="products[${rowIndex}][product_id]" class="product-select w-full border rounded px-2 py-1" required>
                        <option value="">-- Select --</option>
                        ${productOptions()}
                    </select>
                </td>
                <td class="px-2 py-1">
                    <input type="number" min="1" value="1" name="products[${rowIndex}][quantity]"
                        class="qty-input w-full border rounded px-2 py-1 text-center" required>
                </td>
                <td class="px-2 py-1 text-right price-cell">₹0.00</td>
                <td class="px-2 py-1 text-right line-total-cell font-medium">₹0.00</td>
                <td class="px-2 py-1 text-center">
                    <button type="button" class="remove-row text-red-500 hover:text-red-700">✕</button>
                </td>
            `;
            productRows.appendChild(tr);
            rowIndex++;
            recalc();
        }

        productRows.addEventListener('input', recalc);
        productRows.addEventListener('change', recalc);
        productRows.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-row')) {
                const rows = productRows.querySelectorAll('tr');

                if (rows.length <= 1) {
                    alert('At least one product is required.');
                    return;
                }

                e.target.closest('tr').remove();
                recalc();
            }
        });

        document.getElementById('addProduct').addEventListener('click', addRow);
        document.getElementById('amountGiven').addEventListener('input', recalc);

        function recalc() {
            let subtotal = 0,
                tax = 0;

            productRows.querySelectorAll('tr').forEach(tr => {
                const sel = tr.querySelector('.product-select');
                const qty = parseInt(tr.querySelector('.qty-input')?.value || 0);
                const opt = sel?.selectedOptions[0];

                if (!opt || !opt.dataset.price) {
                    tr.querySelector('.price-cell').textContent = '₹0.00';
                    tr.querySelector('.line-total-cell').textContent = '₹0.00';
                    return;
                }

                const price = parseFloat(opt.dataset.price);
                const taxPct = parseFloat(opt.dataset.tax);
                const lineSub = price * qty;
                const lineTax = lineSub * taxPct / 100;

                subtotal += lineSub;
                tax += lineTax;

                tr.querySelector('.price-cell').textContent = '₹' + price.toFixed(2);
                tr.querySelector('.line-total-cell').textContent = '₹' + (lineSub + lineTax).toFixed(2);
            });

            const grand = subtotal + tax;
            document.getElementById('subtotalLabel').textContent = '₹' + subtotal.toFixed(2);
            document.getElementById('taxLabel').textContent = '₹' + tax.toFixed(2);
            document.getElementById('grandTotalLabel').textContent = '₹' + grand.toFixed(2);

            const given = parseFloat(document.getElementById('amountGiven').value || 0);
            const balance = given - grand;
            document.getElementById('balanceLabel').textContent = '₹' + balance.toFixed(2);
            document.getElementById('denomLabel').textContent = balance > 0 ?
                '→ ' + breakdown(balance).join(' + ') :
                '';
        }

        function breakdown(amount) {
            const denoms = [500, 200, 100, 50, 20, 10, 5, 2, 1];
            let n = Math.round(amount);
            const parts = [];
            for (const d of denoms) {
                const c = Math.floor(n / d);
                if (c > 0) {
                    parts.push(`${c}×${d}`);
                    n -= c * d;
                }
            }
            return parts;
        }

        document.getElementById('customerEmail').addEventListener('blur', async (e) => {
            const email = e.target.value;
            if (!email) return;
            try {
                const res = await fetch(`/customers/lookup?email=${encodeURIComponent(email)}`);
                const data = await res.json();
                if (data.exists) document.getElementById('customerName').value = data.name;
            } catch (_) {}
        });

        addRow();
    </script>
@endsection
