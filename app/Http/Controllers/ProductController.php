<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductController extends Controller
{

    public function index(Request $request)
    {
        try {
            $filters = $request->validate([
                'search' => 'nullable|string|max:255',
                'stock_status' => 'nullable|in:all,low,out,in',
            ]);

            $query = Product::query();

            if (!empty($filters['search'])) {
                $s = $filters['search'];
                $query->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhere('unique_code', 'like', "%{$s}%");
                });
            }

            $status = $filters['stock_status'] ?? 'all';
            if ($status === 'low') {
                $query->whereBetween('stock_on_hand', [1, 9]);
            } elseif ($status === 'out') {
                $query->where('stock_on_hand', 0);
            } elseif ($status === 'in') {
                $query->where('stock_on_hand', '>=', 10);
            }

            $products = $query->orderBy('name')->paginate(15)->withQueryString();

            $stats = [
                'total' => Product::count(),
                'low_stock' => Product::whereBetween('stock_on_hand', [1, 9])->count(),
                'out_of_stock' => Product::where('stock_on_hand', 0)->count(),
                'inventory_value' => (float) Product::selectRaw('SUM(price_per_unit * stock_on_hand) as v')->value('v'),
            ];

            return view('products.index', compact('products', 'stats'));
        } catch (Throwable $e) {
            Log::error('Failed to load products', [
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('billing.index')
                ->with('error', 'Unable to load products.');
        }
    }


    public function store(StoreProductRequest $request)
    {
        try {
            $product = Product::create($request->validated());

            return redirect()
                ->route('products.index')
                ->with('success', "Product \"{$product->name}\" created successfully.");
        } catch (Throwable $e) {
            Log::error('Failed to create product', [
                'data' => $request->validated(),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Failed to create product. Please try again.')
                ->withInput();
        }
    }


    public function editData(Product $product)
    {
        try {
            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'unique_code' => $product->unique_code,
                    'price_per_unit' => (float) $product->price_per_unit,
                    'tax_percentage' => (float) $product->tax_percentage,
                    'stock_on_hand' => (int) $product->stock_on_hand,
                    'created_at' => $product->created_at->format('d M Y, H:i'),
                    'updated_at' => $product->updated_at->diffForHumans(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to load product edit data', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load product.',
            ], 500);
        }
    }


    public function update(UpdateProductRequest $request, Product $product)
    {
        try {
            $product->update($request->validated());

            return redirect()
                ->route('products.index')
                ->with('success', "Product \"{$product->name}\" updated successfully.");
        } catch (Throwable $e) {
            Log::error('Failed to update product', [
                'product_id' => $product->id,
                'data' => $request->validated(),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Failed to update product. Please try again.')
                ->withInput();
        }
    }


    public function destroy(Product $product)
    {
        try {
            if ($product->orderItems()->exists()) {
                return redirect()
                    ->route('products.index')
                    ->with('error', "Cannot delete \"{$product->name}\" — it is referenced by existing orders.");
            }

            $name = $product->name;
            $product->delete();

            return redirect()
                ->route('products.index')
                ->with('success', "Product \"{$name}\" deleted successfully.");
        } catch (Throwable $e) {
            Log::error('Failed to delete product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('products.index')
                ->with('error', 'Failed to delete product.');
        }
    }
}
