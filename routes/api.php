<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Models\Order;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/orders', function () {
    return response()->json([
        'success' => true,
        'data' => Order::with(['customer', 'items.product'])
            ->latest()
            ->paginate(15),
    ]);
});
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/customers/{email}/orders', [OrderController::class, 'history']);
Route::get('/products/low-stock', [OrderController::class, 'lowStock']);
