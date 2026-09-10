<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Store Billing</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 min-h-screen">
    <header class="bg-slate-800 text-white px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-6">
            <h1 class="text-xl font-semibold">Store Billing</h1>

            <nav class="flex gap-4 text-sm">
                <a href="{{ route('billing.index') }}"
                    class="px-3 py-1 rounded transition
                    {{ request()->routeIs('billing.index') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-700' }}">
                    📝 New Order
                </a>
                <a href="{{ route('billing.orders.index') }}"
                    class="px-3 py-1 rounded transition
                    {{ request()->routeIs('billing.orders.*') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-700' }}">
                    📋 Orders
                </a>
                <a href="{{ route('customers.index') }}"
                    class="px-3 py-1 rounded transition
                    {{ request()->routeIs('customers.*') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-700' }}">
                    👥 Customers
                </a>
                <a href="{{ route('products.index') }}"
                    class="px-3 py-1 rounded transition
                    {{ request()->routeIs('products.*') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-700' }}">
                    📦 Products
                </a>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-6">
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-800 rounded">
                {{ session('error') }}
            </div>
        @endif
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>
