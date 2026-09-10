@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $customer->name }}</h1>
            <p class="text-sm text-slate-500">{{ $customer->email }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('customers.index') }}"
               class="text-slate-600 hover:text-slate-800 text-sm px-3 py-2">← Back</a>
            <button type="button"
                    class="pay-btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm"
                    data-customer-id="{{ $customer->id }}"
                    data-customer-name="{{ $customer->name }}"
                    data-customer-email="{{ $customer->email }}"
                    data-balance="{{ $balance }}"
                    data-billed="{{ $billed }}"
                    data-paid="{{ $counterPaid + $manualPaid }}">
                💰 Record Payment
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded shadow p-4 border-l-4 border-slate-500">
            <div class="text-xs text-slate-500 uppercase">Total Billed</div>
            <div class="text-2xl font-bold text-slate-800">₹{{ number_format($billed, 2) }}</div>
        </div>
        <div class="bg-white rounded shadow p-4 border-l-4 border-blue-500">
            <div class="text-xs text-slate-500 uppercase">Paid at Counter</div>
            <div class="text-2xl font-bold text-blue-600">₹{{ number_format($counterPaid, 2) }}</div>
        </div>
        <div class="bg-white rounded shadow p-4 border-l-4 border-purple-500">
            <div class="text-xs text-slate-500 uppercase">Manual Payments</div>
            <div class="text-2xl font-bold text-purple-600">₹{{ number_format($manualPaid, 2) }}</div>
        </div>
        <div class="bg-white rounded shadow p-4 border-l-4 {{ $balance > 0 ? 'border-rose-500' : 'border-emerald-500' }}">
            <div class="text-xs text-slate-500 uppercase">Current Balance</div>
            <div class="text-2xl font-bold {{ $balance > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                ₹{{ number_format(abs($balance), 2) }}
                <span class="text-xs font-normal">
                    {{ $balance > 0 ? 'DUE' : ($balance < 0 ? 'CREDIT' : 'SETTLED') }}
                </span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
        <div class="px-4 py-3 border-b bg-slate-50 font-semibold text-slate-700">
            📦 Order History
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-100 text-slate-600 uppercase text-xs">
                <tr>
                    <th class="text-left px-4 py-2">Order #</th>
                    <th class="text-left px-4 py-2">Date</th>
                    <th class="text-right px-4 py-2">Total</th>
                    <th class="text-right px-4 py-2">Paid at Counter</th>
                    <th class="text-right px-4 py-2">Order Due</th>
                    <th class="text-center px-4 py-2">View</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customer->orders as $order)
                <tr class="border-t">
                    <td class="px-4 py-2 font-medium text-blue-600">#{{ $order->id }}</td>
                    <td class="px-4 py-2 text-slate-600">{{ $order->created_at->format('d M Y, H:i') }}</td>
                    <td class="px-4 py-2 text-right">₹{{ number_format($order->grand_total, 2) }}</td>
                    <td class="px-4 py-2 text-right">₹{{ number_format($order->amount_given ?? 0, 2) }}</td>
                    <td class="px-4 py-2 text-right font-medium">
                        @php $orderDue = $order->grand_total - ($order->amount_given ?? 0); @endphp
                        @if($orderDue > 0)
                            <span class="text-rose-600">₹{{ number_format($orderDue, 2) }}</span>
                        @else
                            <span class="text-emerald-600">₹0.00</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-center">
                        <a href="{{ route('billing.invoice', $order) }}" class="text-blue-600 text-xs">View →</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-6 text-slate-400">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
        <div class="px-4 py-3 border-b bg-slate-50 font-semibold text-slate-700">
            💰 Payment History
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-100 text-slate-600 uppercase text-xs">
                <tr>
                    <th class="text-left px-4 py-2">Date</th>
                    <th class="text-left px-4 py-2">Type</th>
                    <th class="text-right px-4 py-2">Amount</th>
                    <th class="text-left px-4 py-2">Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customer->payments as $payment)
                <tr class="border-t">
                    <td class="px-4 py-2 text-slate-600">{{ $payment->created_at->format('d M Y, H:i') }}</td>
                    <td class="px-4 py-2">
                        @if($payment->type === 'due_payment')
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">Due Payment</span>
                        @elseif($payment->type === 'credit')
                            <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded">Credit</span>
                        @else
                            <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded">Refund</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-right font-medium">₹{{ number_format($payment->amount, 2) }}</td>
                    <td class="px-4 py-2 text-slate-500 text-xs">{{ $payment->notes ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-6 text-slate-400">No payments recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection