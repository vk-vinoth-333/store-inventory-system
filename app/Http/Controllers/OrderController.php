<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\LowStockRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private BillingService $billingService) {}

    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->billingService->createOrder($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order,
            ], 201);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function history(string $email): JsonResponse
    {
        $customer = Customer::where('email', $email)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
            ], 404);
        }

        $orders = $customer->orders()
            ->with(['items.product'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'customer' => $customer->only(['id', 'name', 'email']),
            'total_orders' => $orders->count(),
            'orders' => $orders,
        ]);
    }

    public function lowStock(LowStockRequest $request): JsonResponse
    {
        $threshold = $request->getThreshold();

        $products = Product::lowStock($threshold)->get();

        return response()->json([
            'success' => true,
            'threshold' => $threshold,
            'total_products' => $products->count(),
            'products' => $products,
        ]);
    }
}
