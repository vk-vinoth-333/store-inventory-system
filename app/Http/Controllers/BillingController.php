<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\BillingService;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BillingController extends Controller
{
    public function __construct(private BillingService $billingService) {}


    public function index()
    {
        try {
            return view('billing.index', [
                'products' => Product::orderBy('name')->get(),
                'lowStockProducts' => Product::lowStock(10)->get(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to load billing index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('billing.index')
                ->with('error', 'Unable to load products. Please try again.');
        }
    }


    public function lookupCustomer(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $customer = Customer::where('email', $request->email)->first();

            return response()->json([
                'exists' => (bool) $customer,
                'name' => $customer?->name,
            ]);
        } catch (Throwable $e) {
            Log::warning('Customer lookup failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'exists' => false,
                'name' => null,
                'error' => 'Lookup failed',
            ], 200);
        }
    }


    public function generateBill(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_email' => 'required|email',
                'customer_name' => 'required|string|max:255',
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1',
                'amount_given' => 'required|numeric|min:0',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        try {
            $order = $this->billingService->createOrder([
                'customer_email' => $validated['customer_email'],
                'customer_name' => $validated['customer_name'],
                'items' => $validated['products'],
                'amount_given' => $validated['amount_given'],
            ]);

            return redirect()
                ->route('billing.invoice', $order)
                ->with('success', "Order #{$order->id} created successfully.");
        } catch (InsufficientStockException $e) {
            Log::info('Order rejected: insufficient stock', [
                'email' => $validated['customer_email'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        } catch (Throwable $e) {
            Log::error('Order generation failed', [
                'email' => $validated['customer_email'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'Something went wrong while creating the order. Please try again.')
                ->withInput();
        }
    }


    public function invoice(Order $order)
    {
        try {
            $order->load(['customer', 'items.product']);

            return view('billing.invoice', [
                'order' => $order,
                'changeBreakdown' => $this->breakdownChange($order->balance_returned ?? 0),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to load invoice', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('billing.orders.index')
                ->with('error', 'Unable to load that invoice.');
        }
    }


    public function ordersList(Request $request)
    {
        try {
            $filters = $request->validate([
                'email' => 'nullable|string|max:255',
                'from' => 'nullable|date',
                'to' => 'nullable|date|after_or_equal:from',
                'payment_status' => 'nullable|in:all,pending,paid',
            ]);

            $query = Order::with(['customer', 'items.product'])->latest();

            if (!empty($filters['email'])) {
                $query->whereHas('customer', function ($q) use ($filters) {
                    $q->where('email', 'like', '%' . $filters['email'] . '%');
                });
            }

            if (!empty($filters['from'])) {
                $query->whereDate('created_at', '>=', $filters['from']);
            }

            if (!empty($filters['to'])) {
                $query->whereDate('created_at', '<=', $filters['to']);
            }

            if (!empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
                if ($filters['payment_status'] === 'pending') {
                    $query->whereRaw('grand_total > COALESCE(amount_given, 0)');
                } elseif ($filters['payment_status'] === 'paid') {
                    $query->whereRaw('COALESCE(amount_given, 0) >= grand_total');
                }
            }

            $orders = $query->paginate(15)->withQueryString();

            $stats = [
                'total_orders' => Order::count(),
                'total_revenue' => (float) Order::sum('grand_total'),
                'total_tax' => (float) Order::sum('tax'),
                'total_customers' => Customer::count(),
                'total_pending' => (float) Order::whereRaw('grand_total > COALESCE(amount_given, 0)')
                    ->sum(\DB::raw('grand_total - COALESCE(amount_given, 0)')),
            ];

            return view('billing.orders', compact('orders', 'stats'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('billing.orders.index')->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::error('Failed to load orders list', [
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('billing.index')
                ->with('error', 'Unable to load orders.');
        }
    }


    private function breakdownChange(float $amount): array
    {
        $denoms = [500, 200, 100, 50, 20, 10, 5, 2, 1];
        $breakdown = [];
        $amount = (int) round($amount);

        foreach ($denoms as $d) {
            if ($amount >= $d) {
                $count = intdiv($amount, $d);
                $breakdown[$d] = $count;
                $amount -= $count * $d;
            }
        }

        return $breakdown;
    }


    public function editOrderData(Order $order)
    {
        try {
            $order->load(['customer', 'items.product']);

            return response()->json([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'customer_email' => $order->customer->email,
                    'customer_name' => $order->customer->name,
                    'amount_given' => (float) ($order->amount_given ?? 0),
                    'subtotal' => (float) $order->subtotal,
                    'tax' => (float) $order->tax,
                    'grand_total' => (float) $order->grand_total,
                    'balance_returned' => (float) ($order->balance_returned ?? 0),
                    'created_at' => $order->created_at->format('d M Y, H:i'),
                    'items' => $order->items->map(fn($item) => [
                        'product_id' => $item->product_id,
                        'name' => $item->product->name,
                        'code' => $item->product->unique_code,
                        'price_per_unit' => (float) $item->price_per_unit,
                        'tax_percentage' => (float) $item->tax_percentage,
                        'quantity' => (int) $item->quantity,
                        'stock_on_hand' => (int) $item->product->stock_on_hand,
                    ]),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to load order edit data', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load order.',
            ], 500);
        }
    }

    /**
     * ✅ Update an order.
     */
    public function updateOrder(UpdateOrderRequest $request, Order $order)
    {
        try {
            $this->billingService->updateOrder($order, $request->validated());

            return redirect()
                ->route('billing.orders.index')
                ->with('success', "Order #{$order->id} updated successfully. Stock and totals recalculated.");
        } catch (InsufficientStockException $e) {
            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        } catch (Throwable $e) {
            Log::error('Failed to update order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'Failed to update order. Please try again.')
                ->withInput();
        }
    }

    /**
     * ✅ Delete an order (restores stock).
     */
    public function destroyOrder(Order $order)
    {
        try {
            $orderId = $order->id;
            $this->billingService->deleteOrder($order);

            return redirect()
                ->route('billing.orders.index')
                ->with('success', "Order #{$orderId} deleted successfully. Stock restored.");
        } catch (Throwable $e) {
            Log::error('Failed to delete order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('billing.orders.index')
                ->with('error', 'Failed to delete order.');
        }
    }
}
