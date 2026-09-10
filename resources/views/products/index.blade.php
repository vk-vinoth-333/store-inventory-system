@extends('layouts.app')

@section('content')
    <div class="space-y-6">

        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">📦 Products</h1>
                <p class="text-sm text-slate-500">Manage your product catalog</p>
            </div>
            <button type="button" id="btnCreateProduct"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                + Add Product
            </button>
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
                <div class="text-xs text-slate-500 uppercase">Total Products</div>
                <div class="text-2xl font-bold text-slate-800">{{ number_format($stats['total']) }}</div>
            </div>
            <div class="bg-white rounded shadow p-4 border-l-4 border-amber-500">
                <div class="text-xs text-slate-500 uppercase">Low Stock (1–9)</div>
                <div class="text-2xl font-bold text-amber-600">{{ number_format($stats['low_stock']) }}</div>
            </div>
            <div class="bg-white rounded shadow p-4 border-l-4 border-rose-500">
                <div class="text-xs text-slate-500 uppercase">Out of Stock</div>
                <div class="text-2xl font-bold text-rose-600">{{ number_format($stats['out_of_stock']) }}</div>
            </div>
            <div class="bg-white rounded shadow p-4 border-l-4 border-emerald-500">
                <div class="text-xs text-slate-500 uppercase">Inventory Value</div>
                <div class="text-2xl font-bold text-emerald-600">₹{{ number_format($stats['inventory_value'], 2) }}</div>
            </div>
        </div>

        <div class="bg-white rounded shadow p-4">
            <form method="GET" action="{{ route('products.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or code"
                        class="w-full border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Stock Status</label>
                    <select name="stock_status" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="all" @selected(request('stock_status', 'all') === 'all')>All</option>
                        <option value="in" @selected(request('stock_status') === 'in')>In Stock (≥10)</option>
                        <option value="low" @selected(request('stock_status') === 'low')>Low Stock (1–9)</option>
                        <option value="out" @selected(request('stock_status') === 'out')>Out of Stock</option>
                    </select>
                </div>
                <div class="flex items-end gap-2 md:col-span-2">
                    <button type="submit" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded text-sm">
                        Filter
                    </button>
                    <a href="{{ route('products.index') }}" class="text-slate-500 hover:text-slate-700 px-3 py-2 text-sm">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-700 uppercase text-xs">
                    <tr>
                        <th class="text-left px-4 py-3">Product</th>
                        <th class="text-left px-4 py-3">Code</th>
                        <th class="text-right px-4 py-3">Price</th>
                        <th class="text-right px-4 py-3">Tax %</th>
                        <th class="text-right px-4 py-3">Price + Tax</th>
                        <th class="text-center px-4 py-3">Stock</th>
                        <th class="text-center px-4 py-3">Status</th>
                        <th class="text-center px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $stock = (int) $product->stock_on_hand;
                            $status = $stock === 0 ? 'out' : ($stock < 10 ? 'low' : 'in');
                        @endphp
                        <tr class="border-t hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $product->name }}</td>
                            <td class="px-4 py-3 text-slate-600 font-mono text-xs">{{ $product->unique_code }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">
                                ₹{{ number_format($product->price_per_unit, 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">
                                {{ number_format($product->tax_percentage, 2) }}%</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-800">
                                ₹{{ number_format($product->price_per_unit * (1 + $product->tax_percentage / 100), 2) }}
                            </td>
                            <td class="px-4 py-3 text-center font-semibold">{{ $stock }}</td>
                            <td class="px-4 py-3 text-center">
                                @if ($status === 'out')
                                    <span class="text-xs bg-rose-100 text-rose-700 px-2 py-1 rounded">Out of Stock</span>
                                @elseif($status === 'low')
                                    <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded">Low Stock</span>
                                @else
                                    <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-1 rounded">In Stock</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button"
                                        class="btn-view-product text-xs text-slate-600 hover:text-slate-800 font-medium"
                                        data-id="{{ $product->id }}">
                                        View
                                    </button>
                                    <button type="button"
                                        class="btn-edit-product text-xs text-blue-600 hover:text-blue-800 font-medium"
                                        data-id="{{ $product->id }}">
                                        Edit
                                    </button>
                                    <button type="button"
                                        class="btn-delete-product text-xs text-rose-600 hover:text-rose-800 font-medium"
                                        data-id="{{ $product->id }}" data-name="{{ $product->name }}">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                <div class="text-4xl mb-2">📦</div>
                                <div>No products found.</div>
                                <button type="button"
                                    class="btn-create-product-empty inline-block mt-3 text-blue-600 hover:text-blue-800 text-sm">
                                    Create your first product →
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="flex justify-center">
                {{ $products->links() }}
            </div>
        @endif
    </div>


    <div id="productFormModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
            <form method="POST" id="productForm" action="{{ route('products.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <div class="flex justify-between items-center px-5 py-3 border-b">
                    <h3 class="font-semibold text-slate-800" id="productFormTitle">➕ Add Product</h3>
                    <button type="button" onclick="closeProductFormModal()"
                        class="text-slate-400 hover:text-slate-600 text-xl leading-none">×</button>
                </div>

                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Product Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="pName" maxlength="255"
                            class="w-full border rounded px-3 py-2 text-sm" required>
                        <p class="text-xs text-rose-500 mt-1 hidden" data-error="name"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Unique Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="unique_code" id="pCode" maxlength="50"
                            class="w-full border rounded px-3 py-2 text-sm font-mono uppercase" placeholder="e.g. WM001"
                            required>
                        <p class="text-xs text-slate-400 mt-1">Letters, numbers, - and _ only.</p>
                        <p class="text-xs text-rose-500 mt-1 hidden" data-error="unique_code"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">
                                Price / Unit (₹) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" name="price_per_unit" id="pPrice"
                                class="w-full border rounded px-3 py-2 text-sm" required>
                            <p class="text-xs text-rose-500 mt-1 hidden" data-error="price_per_unit"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">
                                Tax % <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" max="100" name="tax_percentage"
                                id="pTax" class="w-full border rounded px-3 py-2 text-sm" required>
                            <p class="text-xs text-rose-500 mt-1 hidden" data-error="tax_percentage"></p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Stock on Hand <span class="text-red-500">*</span>
                        </label>
                        <input type="number" min="0" name="stock_on_hand" id="pStock"
                            class="w-full border rounded px-3 py-2 text-sm" required>
                        <p class="text-xs text-rose-500 mt-1 hidden" data-error="stock_on_hand"></p>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded p-3 text-sm">
                        <div class="flex justify-between">
                            <span>Price + Tax:</span>
                            <span class="font-bold" id="pPreview">₹0.00</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 px-5 py-3 border-t bg-slate-50">
                    <button type="button" onclick="closeProductFormModal()"
                        class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">
                        Cancel
                    </button>
                    <button type="submit" id="productFormSubmit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                        Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>


    <div id="productViewModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
            <div class="flex justify-between items-center px-5 py-3 border-b">
                <h3 class="font-semibold text-slate-800">🔍 Product Details</h3>
                <button type="button" onclick="closeProductViewModal()"
                    class="text-slate-400 hover:text-slate-600 text-xl leading-none">×</button>
            </div>
            <div class="p-5 space-y-3 text-sm" id="viewBody">
                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Name:</span>
                    <span class="font-medium" id="vName">—</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Code:</span>
                    <span class="font-mono" id="vCode">—</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Price / Unit:</span>
                    <span id="vPrice">—</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Tax %:</span>
                    <span id="vTax">—</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Price + Tax:</span>
                    <span class="font-semibold text-emerald-600" id="vFinal">—</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Stock on Hand:</span>
                    <span class="font-semibold" id="vStock">—</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Status:</span>
                    <span id="vStatus">—</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Created:</span>
                    <span id="vCreated">—</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Last Updated:</span>
                    <span id="vUpdated">—</span>
                </div>
            </div>
            <div class="flex justify-end gap-2 px-5 py-3 border-t bg-slate-50">
                <button type="button" onclick="closeProductViewModal()"
                    class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">
                    Close
                </button>
                <button type="button" id="viewEditBtn"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                    ✏️ Edit
                </button>
            </div>
        </div>
    </div>


    <div id="productDeleteModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <form method="POST" id="deleteForm" action="">
                @csrf
                @method('DELETE')

                <div class="p-5">
                    <div class="flex items-start gap-3">
                        <div class="bg-rose-100 rounded-full p-2">
                            <span class="text-rose-600 text-xl">⚠️</span>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-800">Delete Product?</h3>
                            <p class="text-sm text-slate-600 mt-1">
                                You are about to delete
                                <span class="font-semibold text-slate-800" id="deleteProductName">this product</span>.
                                This action cannot be undone.
                            </p>
                            <p class="text-xs text-slate-500 mt-2">
                                Note: products referenced by existing orders cannot be deleted.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 px-5 py-3 border-t bg-slate-50">
                    <button type="button" onclick="closeProductDeleteModal()"
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
            const formModal = document.getElementById('productFormModal');
            const viewModal = document.getElementById('productViewModal');
            const deleteModal = document.getElementById('productDeleteModal');

            const form = document.getElementById('productForm');
            const formTitle = document.getElementById('productFormTitle');
            const formMethod = document.getElementById('formMethod');
            const formSubmit = document.getElementById('productFormSubmit');

            const pName = document.getElementById('pName');
            const pCode = document.getElementById('pCode');
            const pPrice = document.getElementById('pPrice');
            const pTax = document.getElementById('pTax');
            const pStock = document.getElementById('pStock');
            const pPreview = document.getElementById('pPreview');

            document.getElementById('btnCreateProduct').addEventListener('click', openCreateModal);
            document.querySelectorAll('.btn-create-product-empty').forEach(b => b.addEventListener('click', openCreateModal));

            function openCreateModal() {
                resetForm();
                formTitle.textContent = '➕ Add Product';
                formSubmit.textContent = 'Create Product';
                formMethod.value = 'POST';
                form.action = '{{ route('products.store') }}';
                formModal.classList.remove('hidden');
                setTimeout(() => pName.focus(), 50);
            }

            document.querySelectorAll('.btn-edit-product').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const id = btn.dataset.id;
                    try {
                        const res = await fetch(`/products/${id}/edit-data`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) throw new Error('Failed to load');
                        const {
                            product
                        } = await res.json();

                        resetForm();
                        formTitle.textContent = `✏️ Edit Product #${product.id}`;
                        formSubmit.textContent = 'Save Changes';
                        formMethod.value = 'PUT';
                        form.action = `/products/${product.id}`;

                        pName.value = product.name;
                        pCode.value = product.unique_code;
                        pPrice.value = product.price_per_unit;
                        pTax.value = product.tax_percentage;
                        pStock.value = product.stock_on_hand;

                        updatePreview();
                        formModal.classList.remove('hidden');
                        setTimeout(() => pName.focus(), 50);

                    } catch (err) {
                        alert('Could not load product. Please try again.');
                    }
                });
            });

            document.querySelectorAll('.btn-view-product').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const id = btn.dataset.id;
                    try {
                        const res = await fetch(`/products/${id}/edit-data`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) throw new Error('Failed to load');
                        const {
                            product
                        } = await res.json();

                        document.getElementById('vName').textContent = product.name;
                        document.getElementById('vCode').textContent = product.unique_code;
                        document.getElementById('vPrice').textContent = '₹' + product.price_per_unit
                            .toFixed(2);
                        document.getElementById('vTax').textContent = product.tax_percentage.toFixed(2) +
                            '%';

                        const finalPrice = product.price_per_unit * (1 + product.tax_percentage / 100);
                        document.getElementById('vFinal').textContent = '₹' + finalPrice.toFixed(2);
                        document.getElementById('vStock').textContent = product.stock_on_hand;

                        const stock = product.stock_on_hand;
                        const statusEl = document.getElementById('vStatus');
                        if (stock === 0) {
                            statusEl.innerHTML =
                                '<span class="text-xs bg-rose-100 text-rose-700 px-2 py-1 rounded">Out of Stock</span>';
                        } else if (stock < 10) {
                            statusEl.innerHTML =
                                '<span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded">Low Stock</span>';
                        } else {
                            statusEl.innerHTML =
                                '<span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-1 rounded">In Stock</span>';
                        }

                        document.getElementById('vCreated').textContent = product.created_at;
                        document.getElementById('vUpdated').textContent = product.updated_at;

                        document.getElementById('viewEditBtn').onclick = () => {
                            closeProductViewModal();
                            document.querySelector(`.btn-edit-product[data-id="${id}"]`).click();
                        };

                        viewModal.classList.remove('hidden');

                    } catch (err) {
                        alert('Could not load product. Please try again.');
                    }
                });
            });

            document.querySelectorAll('.btn-delete-product').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    const name = btn.dataset.name;
                    document.getElementById('deleteProductName').textContent = `"${name}"`;
                    document.getElementById('deleteForm').action = `/products/${id}`;
                    deleteModal.classList.remove('hidden');
                });
            });

            function closeProductFormModal() {
                formModal.classList.add('hidden');
            }

            function closeProductViewModal() {
                viewModal.classList.add('hidden');
            }

            function closeProductDeleteModal() {
                deleteModal.classList.add('hidden');
            }

            [formModal, viewModal, deleteModal].forEach(m => {
                m.addEventListener('click', (e) => {
                    if (e.target === m) m.classList.add('hidden');
                });
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    formModal.classList.add('hidden');
                    viewModal.classList.add('hidden');
                    deleteModal.classList.add('hidden');
                }
            });

            function resetForm() {
                form.reset();
                document.querySelectorAll('[data-error]').forEach(el => {
                    el.textContent = '';
                    el.classList.add('hidden');
                });
                pPreview.textContent = '₹0.00';
                pCode.classList.remove('border-red-500');
            }

            function updatePreview() {
                const price = parseFloat(pPrice.value || 0);
                const tax = parseFloat(pTax.value || 0);
                const final = price * (1 + tax / 100);
                pPreview.textContent = '₹' + final.toFixed(2);
            }
            pPrice.addEventListener('input', updatePreview);
            pTax.addEventListener('input', updatePreview);

            pCode.addEventListener('input', () => {
                pCode.value = pCode.value.toUpperCase();
            });

            @if ($errors->any())
                (function() {
                    openCreateModal();
                    @foreach ($errors->keys() as $key)
                        (function() {
                            const el = document.querySelector('[data-error="{{ $key }}"]');
                            if (el) {
                                el.textContent = @json($errors->first($key));
                                el.classList.remove('hidden');
                            }
                        })();
                    @endforeach
                    @if (old('name'))
                        pName.value = @json(old('name'));
                    @endif
                    @if (old('unique_code'))
                        pCode.value = @json(old('unique_code'));
                    @endif
                    @if (old('price_per_unit'))
                        pPrice.value = @json(old('price_per_unit'));
                    @endif
                    @if (old('tax_percentage'))
                        pTax.value = @json(old('tax_percentage'));
                    @endif
                    @if (old('stock_on_hand'))
                        pStock.value = @json(old('stock_on_hand'));
                    @endif
                    updatePreview();
                })();
            @endif
        </script>
    @endpush
@endsection
