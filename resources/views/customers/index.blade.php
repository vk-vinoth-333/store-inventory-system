@extends('layouts.app')

@section('content')
    <div class="space-y-6">

        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">👥 Customers</h1>
                <p class="text-sm text-slate-500">Manage customer balances, dues and credits</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('billing.index') }}" class="text-slate-600 hover:text-slate-800 px-4 py-2 text-sm">
                    + New Order
                </a>
                <button type="button" id="btnCreateCustomer"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                    + Add Customer
                </button>
            </div>
        </div>

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

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded shadow p-4 border-l-4 border-blue-500">
                <div class="text-xs text-slate-500 uppercase">Total Customers</div>
                <div class="text-2xl font-bold text-slate-800">{{ number_format($stats['total_customers']) }}</div>
            </div>
            <div class="bg-white rounded shadow p-4 border-l-4 border-rose-500">
                <div class="text-xs text-slate-500 uppercase">Customers With Due</div>
                <div class="text-2xl font-bold text-rose-600">{{ number_format($stats['with_due']) }}</div>
            </div>
            <div class="bg-white rounded shadow p-4 border-l-4 border-red-500">
                <div class="text-xs text-slate-500 uppercase">Total Outstanding</div>
                <div class="text-2xl font-bold text-red-600">₹{{ number_format($stats['total_due'], 2) }}</div>
            </div>
            <div class="bg-white rounded shadow p-4 border-l-4 border-emerald-500">
                <div class="text-xs text-slate-500 uppercase">Total Credit</div>
                <div class="text-2xl font-bold text-emerald-600">₹{{ number_format($stats['total_credit'], 2) }}</div>
            </div>
        </div>

        <div class="bg-white rounded shadow p-4">
            <form method="GET" action="{{ route('customers.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or email"
                        class="w-full border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                    <select name="status" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="all" @selected(request('status', 'all') === 'all')>All</option>
                        <option value="due" @selected(request('status') === 'due')>Has Due</option>
                        <option value="credit" @selected(request('status') === 'credit')>Has Credit</option>
                        <option value="clear" @selected(request('status') === 'clear')>Settled</option>
                    </select>
                </div>
                <div class="flex items-end gap-2 md:col-span-2">
                    <button type="submit" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded text-sm">
                        Filter
                    </button>
                    <a href="{{ route('customers.index') }}" class="text-slate-500 hover:text-slate-700 px-3 py-2 text-sm">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-700 uppercase text-xs">
                    <tr>
                        <th class="text-left px-4 py-3">Customer</th>
                        <th class="text-center px-4 py-3">Orders</th>
                        <th class="text-right px-4 py-3">Total Billed</th>
                        <th class="text-right px-4 py-3">Paid at Counter</th>
                        <th class="text-right px-4 py-3">Manual Payments</th>
                        <th class="text-right px-4 py-3">Balance</th>
                        <th class="text-center px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        @php
                            $billed = (float) ($customer->total_billed ?? 0);
                            $counterPaid = (float) ($customer->total_counter_paid ?? 0);
                            $duePaid = (float) ($customer->total_due_payments ?? 0);
                            $credits = (float) ($customer->total_credits ?? 0);
                            $refunds = (float) ($customer->total_refunds ?? 0);

                            $balance =
                                $customer->computed_balance ??
                                round($billed - $counterPaid - $duePaid - $credits + $refunds, 2);

                            $netManual = $duePaid + $credits - $refunds;
                        @endphp
                        <tr class="border-t hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $customer->name }}</div>
                                <div class="text-xs text-slate-500">{{ $customer->email }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="inline-flex items-center bg-slate-100 text-slate-700 text-xs font-medium px-2 py-1 rounded">
                                    {{ $customer->orders_count ?? 0 }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700">₹{{ number_format($billed, 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">₹{{ number_format($counterPaid, 2) }}</td>

                            <td class="px-4 py-3 text-right text-slate-700">
                                ₹{{ number_format($netManual, 2) }}
                                @if ($refunds > 0)
                                    <div class="text-[10px] text-amber-600">-₹{{ number_format($refunds, 2) }} refund</div>
                                @endif
                                @if ($credits > 0)
                                    <div class="text-[10px] text-emerald-600">+₹{{ number_format($credits, 2) }} credit
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right">
                                @if ($balance > 0)
                                    <span class="font-bold text-rose-600">
                                        ₹{{ number_format($balance, 2) }}<br>
                                        <span class="text-xs font-normal text-rose-500">DUE</span>
                                    </span>
                                @elseif($balance < 0)
                                    <span class="font-bold text-emerald-600">
                                        ₹{{ number_format(abs($balance), 2) }}<br>
                                        <span class="text-xs font-normal text-emerald-500">CREDIT</span>
                                    </span>
                                @else
                                    <span class="font-medium text-slate-500">₹0.00<br>
                                        <span class="text-xs text-slate-400">SETTLED</span>
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button"
                                        class="btn-view-customer text-xs text-slate-600 hover:text-slate-800 font-medium"
                                        data-id="{{ $customer->id }}">View</button>
                                    <button type="button"
                                        class="btn-edit-customer text-xs text-blue-600 hover:text-blue-800 font-medium"
                                        data-id="{{ $customer->id }}">Edit</button>
                                    <button type="button"
                                        class="pay-btn text-xs font-medium bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1 rounded"
                                        data-customer-id="{{ $customer->id }}" data-customer-name="{{ $customer->name }}"
                                        data-customer-email="{{ $customer->email }}" data-balance="{{ $balance }}"
                                        data-billed="{{ $billed }}" data-paid="{{ $counterPaid + $netManual }}">💰
                                        Pay</button>
                                    <button type="button"
                                        class="btn-delete-customer text-xs text-rose-600 hover:text-rose-800 font-medium"
                                        data-id="{{ $customer->id }}" data-name="{{ $customer->name }}">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12 text-slate-400">
                                <div class="text-4xl mb-2">👤</div>
                                <div>No customers found.</div>
                                <button type="button"
                                    class="btn-create-customer-empty inline-block mt-3 text-blue-600 hover:text-blue-800 text-sm">
                                    Create your first customer →
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($customers->hasPages())
            <div class="flex justify-center">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

    <div id="paymentModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <form method="POST" id="paymentForm" action="">
                @csrf

                <div class="flex justify-between items-center px-5 py-3 border-b">
                    <h3 class="font-semibold text-slate-800">💰 Record Payment</h3>
                    <button type="button" onclick="closePaymentModal()"
                        class="text-slate-400 hover:text-slate-600 text-xl leading-none">×</button>
                </div>

                <div class="p-5 space-y-4">
                    <div class="bg-slate-50 rounded p-3 text-sm">
                        <div class="font-medium text-slate-800" id="mCustomerName">—</div>
                        <div class="text-xs text-slate-500 mb-2" id="mCustomerEmail">—</div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>Total Billed:</div>
                            <div class="text-right font-medium" id="mBilled">₹0.00</div>
                            <div>Total Paid:</div>
                            <div class="text-right font-medium" id="mPaid">₹0.00</div>
                            <div>Current Balance:</div>
                            <div class="text-right font-bold" id="mBalance">₹0.00</div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Quick Amount</label>
                        <div class="flex gap-2 flex-wrap" id="quickAmounts"></div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Amount <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">₹</span>
                            <input type="number" step="0.01" min="0.01" name="amount" id="mAmount"
                                class="w-full border rounded pl-7 pr-3 py-2 text-sm" required>
                        </div>
                        <p class="text-xs text-slate-500 mt-1" id="mAmountHint">
                            Enter amount received from customer
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Payment Type <span class="text-red-500">*</span>
                        </label>
                        <select name="type" id="mType" class="w-full border rounded px-3 py-2 text-sm" required>
                            <option value="due_payment">💵 Due Payment (Customer pays outstanding)</option>
                            <option value="credit">🎁 Credit (Advance / extra amount)</option>
                            <option value="refund">↩️ Refund (Return to customer)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Notes (optional)</label>
                        <textarea name="notes" rows="2" maxlength="500" class="w-full border rounded px-3 py-2 text-sm"
                            placeholder="e.g. Paid in cash at counter"></textarea>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded p-3 text-sm">
                        <div class="flex justify-between">
                            <span>New Balance will be:</span>
                            <span class="font-bold" id="mNewBalance">₹0.00</span>
                        </div>
                        <div class="text-xs text-slate-500 mt-1" id="mNewBalanceHint">
                            Enter an amount to see the effect
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 px-5 py-3 border-t bg-slate-50">
                    <button type="button" onclick="closePaymentModal()"
                        class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                        Save Payment
                    </button>
                </div>
            </form>
        </div>
    </div>


    <div id="customerFormModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <form method="POST" id="customerForm" action="{{ route('customers.store') }}">
                @csrf
                <input type="hidden" name="_method" id="customerFormMethod" value="POST">

                <div class="flex justify-between items-center px-5 py-3 border-b">
                    <h3 class="font-semibold text-slate-800" id="customerFormTitle">➕ Add Customer</h3>
                    <button type="button" onclick="closeCustomerFormModal()"
                        class="text-slate-400 hover:text-slate-600 text-xl leading-none">×</button>
                </div>

                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="cName" maxlength="255"
                            class="w-full border rounded px-3 py-2 text-sm" required>
                        <p class="text-xs text-rose-500 mt-1 hidden" data-error="name"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" id="cEmail" maxlength="255"
                            class="w-full border rounded px-3 py-2 text-sm" placeholder="e.g. thomas@example.com"
                            required>
                        <p class="text-xs text-slate-400 mt-1">Must be unique. Used as the customer identifier.</p>
                        <p class="text-xs text-rose-500 mt-1 hidden" data-error="email"></p>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded p-3 text-xs text-slate-600">
                        ℹ️ New customers start with ₹0.00 balance. Balances change as orders
                        are placed and payments recorded.
                    </div>
                </div>

                <div class="flex justify-end gap-2 px-5 py-3 border-t bg-slate-50">
                    <button type="button" onclick="closeCustomerFormModal()"
                        class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">
                        Cancel
                    </button>
                    <button type="submit" id="customerFormSubmit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                        Save Customer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="customerViewModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="flex justify-between items-center px-5 py-3 border-b">
                <h3 class="font-semibold text-slate-800">🔍 Customer Details</h3>
                <button type="button" onclick="closeCustomerViewModal()"
                    class="text-slate-400 hover:text-slate-600 text-xl leading-none">×</button>
            </div>
            <div class="p-5 space-y-3 text-sm">
                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Name:</span>
                    <span class="font-medium" id="vcName">—</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Email:</span>
                    <span id="vcEmail">—</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Customer ID:</span>
                    <span class="font-mono" id="vcId">—</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Total Orders:</span>
                    <span class="font-medium" id="vcOrders">—</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Created:</span>
                    <span id="vcCreated">—</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Last Updated:</span>
                    <span id="vcUpdated">—</span>
                </div>
            </div>
            <div class="flex justify-end gap-2 px-5 py-3 border-t bg-slate-50">
                <button type="button" onclick="closeCustomerViewModal()"
                    class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">
                    Close
                </button>
                <button type="button" id="viewCustomerEditBtn"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                    ✏️ Edit
                </button>
            </div>
        </div>
    </div>


    <div id="customerDeleteModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <form method="POST" id="deleteCustomerForm" action="">
                @csrf
                @method('DELETE')

                <div class="p-5">
                    <div class="flex items-start gap-3">
                        <div class="bg-rose-100 rounded-full p-2">
                            <span class="text-rose-600 text-xl">⚠️</span>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-800">Delete Customer?</h3>
                            <p class="text-sm text-slate-600 mt-1">
                                You are about to delete
                                <span class="font-semibold text-slate-800" id="deleteCustomerName">this customer</span>.
                                This action cannot be undone.
                            </p>
                            <p class="text-xs text-slate-500 mt-2">
                                Note: customers with existing orders or payment history cannot be deleted.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 px-5 py-3 border-t bg-slate-50">
                    <button type="button" onclick="closeCustomerDeleteModal()"
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
            const paymentModal = document.getElementById('paymentModal');
            const paymentForm = document.getElementById('paymentForm');
            const mAmount = document.getElementById('mAmount');
            const mType = document.getElementById('mType');
            let currentBalance = 0;

            document.querySelectorAll('.pay-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.customerId;
                    const name = btn.dataset.customerName;
                    const email = btn.dataset.customerEmail;
                    const balance = parseFloat(btn.dataset.balance || 0);
                    const billed = parseFloat(btn.dataset.billed || 0);
                    const paid = parseFloat(btn.dataset.paid || 0);

                    currentBalance = balance;

                    document.getElementById('mCustomerName').textContent = name;
                    document.getElementById('mCustomerEmail').textContent = email;
                    document.getElementById('mBilled').textContent = '₹' + billed.toFixed(2);
                    document.getElementById('mPaid').textContent = '₹' + paid.toFixed(2);
                    document.getElementById('mBalance').textContent = '₹' + balance.toFixed(2);

                    const mBal = document.getElementById('mBalance');
                    mBal.className = 'text-right font-bold ' +
                        (balance > 0 ? 'text-rose-600' : balance < 0 ? 'text-emerald-600' : 'text-slate-500');

                    paymentForm.action = `/customers/${id}/payments`;

                    const quick = document.getElementById('quickAmounts');
                    quick.innerHTML = '';
                    const suggestions = new Set();

                    if (balance > 0) {
                        suggestions.add(balance);
                        suggestions.add(Math.round(balance / 2 * 100) / 100);
                        suggestions.add(100);
                        suggestions.add(500);
                        mType.value = 'due_payment';
                    } else {
                        suggestions.add(100);
                        suggestions.add(500);
                        suggestions.add(1000);
                        mType.value = 'credit';
                    }

                    [...suggestions]
                    .filter(v => v > 0)
                        .sort((a, b) => a - b)
                        .slice(0, 4)
                        .forEach(v => {
                            const b = document.createElement('button');
                            b.type = 'button';
                            b.className =
                                'text-xs bg-slate-100 hover:bg-slate-200 border rounded px-2 py-1';
                            b.textContent = '₹' + v.toFixed(2);
                            b.onclick = () => {
                                mAmount.value = v.toFixed(2);
                                updatePreview();
                            };
                            quick.appendChild(b);
                        });

                    mAmount.value = balance > 0 ? balance.toFixed(2) : '';
                    updatePreview();

                    paymentModal.classList.remove('hidden');
                    setTimeout(() => mAmount.focus(), 50);
                });
            });

            function closePaymentModal() {
                paymentModal.classList.add('hidden');
                paymentForm.reset();
                currentBalance = 0;
            }

            paymentModal.addEventListener('click', (e) => {
                if (e.target === paymentModal) closePaymentModal();
            });

            function updatePreview() {
                const amt = parseFloat(mAmount.value || 0);
                const type = mType.value;

                let newBalance = currentBalance;
                if (type === 'due_payment') {
                    newBalance = currentBalance - amt;
                } else if (type === 'credit') {
                    newBalance = currentBalance - amt;
                } else if (type === 'refund') {
                    newBalance = currentBalance + amt;
                }

                newBalance = Math.round(newBalance * 100) / 100;

                const el = document.getElementById('mNewBalance');
                const hint = document.getElementById('mNewBalanceHint');

                el.textContent = '₹' + newBalance.toFixed(2);
                el.className = 'font-bold ' +
                    (newBalance > 0 ? 'text-rose-600' : newBalance < 0 ? 'text-emerald-600' : 'text-slate-700');

                if (newBalance > 0) {
                    hint.textContent = 'Customer will still owe ₹' + newBalance.toFixed(2);
                } else if (newBalance < 0) {
                    hint.textContent = 'Customer will have ₹' + Math.abs(newBalance).toFixed(2) + ' credit';
                } else {
                    hint.textContent = 'Customer will be fully settled ✓';
                }
            }

            mAmount.addEventListener('input', updatePreview);
            mType.addEventListener('change', updatePreview);

            const customerFormModal = document.getElementById('customerFormModal');
            const customerViewModal = document.getElementById('customerViewModal');
            const customerDeleteModal = document.getElementById('customerDeleteModal');

            const customerForm = document.getElementById('customerForm');
            const customerFormTitle = document.getElementById('customerFormTitle');
            const customerFormMethod = document.getElementById('customerFormMethod');
            const customerFormSubmit = document.getElementById('customerFormSubmit');

            const cName = document.getElementById('cName');
            const cEmail = document.getElementById('cEmail');

            document.getElementById('btnCreateCustomer').addEventListener('click', openCreateCustomerModal);
            document.querySelectorAll('.btn-create-customer-empty').forEach(b => b.addEventListener('click',
                openCreateCustomerModal));

            function openCreateCustomerModal() {
                resetCustomerForm();
                customerFormTitle.textContent = '➕ Add Customer';
                customerFormSubmit.textContent = 'Create Customer';
                customerFormMethod.value = 'POST';
                customerForm.action = '{{ route('customers.store') }}';
                customerFormModal.classList.remove('hidden');
                setTimeout(() => cName.focus(), 50);
            }

            document.querySelectorAll('.btn-edit-customer').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const id = btn.dataset.id;
                    try {
                        const res = await fetch(`/customers/${id}/edit-data`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) throw new Error('Failed to load');
                        const {
                            customer
                        } = await res.json();

                        resetCustomerForm();
                        customerFormTitle.textContent = `✏️ Edit Customer #${customer.id}`;
                        customerFormSubmit.textContent = 'Save Changes';
                        customerFormMethod.value = 'PUT';
                        customerForm.action = `/customers/${customer.id}`;

                        cName.value = customer.name;
                        cEmail.value = customer.email;

                        customerFormModal.classList.remove('hidden');
                        setTimeout(() => cName.focus(), 50);

                    } catch (err) {
                        alert('Could not load customer. Please try again.');
                    }
                });
            });

            document.querySelectorAll('.btn-view-customer').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const id = btn.dataset.id;
                    try {
                        const res = await fetch(`/customers/${id}/edit-data`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) throw new Error('Failed to load');
                        const {
                            customer
                        } = await res.json();

                        document.getElementById('vcName').textContent = customer.name;
                        document.getElementById('vcEmail').textContent = customer.email;
                        document.getElementById('vcId').textContent = '#' + customer.id;
                        document.getElementById('vcOrders').textContent = customer.orders_count;
                        document.getElementById('vcCreated').textContent = customer.created_at;
                        document.getElementById('vcUpdated').textContent = customer.updated_at;

                        document.getElementById('viewCustomerEditBtn').onclick = () => {
                            closeCustomerViewModal();
                            document.querySelector(`.btn-edit-customer[data-id="${id}"]`).click();
                        };

                        customerViewModal.classList.remove('hidden');

                    } catch (err) {
                        alert('Could not load customer. Please try again.');
                    }
                });
            });

            document.querySelectorAll('.btn-delete-customer').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    const name = btn.dataset.name;
                    document.getElementById('deleteCustomerName').textContent = `"${name}"`;
                    document.getElementById('deleteCustomerForm').action = `/customers/${id}`;
                    customerDeleteModal.classList.remove('hidden');
                });
            });

            function closeCustomerFormModal() {
                customerFormModal.classList.add('hidden');
            }

            function closeCustomerViewModal() {
                customerViewModal.classList.add('hidden');
            }

            function closeCustomerDeleteModal() {
                customerDeleteModal.classList.add('hidden');
            }

            [customerFormModal, customerViewModal, customerDeleteModal].forEach(m => {
                m.addEventListener('click', (e) => {
                    if (e.target === m) m.classList.add('hidden');
                });
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    customerFormModal.classList.add('hidden');
                    customerViewModal.classList.add('hidden');
                    customerDeleteModal.classList.add('hidden');
                }
            });

            function resetCustomerForm() {
                customerForm.reset();
                document.querySelectorAll('#customerFormModal [data-error]').forEach(el => {
                    el.textContent = '';
                    el.classList.add('hidden');
                });
            }

            @if ($errors->any())
                (function() {
                    openCreateCustomerModal();
                    @foreach ($errors->keys() as $key)
                        (function() {
                            const el = document.querySelector('#customerFormModal [data-error="{{ $key }}"]');
                            if (el) {
                                el.textContent = @json($errors->first($key));
                                el.classList.remove('hidden');
                            }
                        })();
                    @endforeach
                    @if (old('name'))
                        cName.value = @json(old('name'));
                    @endif
                    @if (old('email'))
                        cEmail.value = @json(old('email'));
                    @endif
                })();
            @endif
        </script>
    @endpush
@endsection
