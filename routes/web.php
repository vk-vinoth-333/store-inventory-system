<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;



Route::get('/', [BillingController::class, 'index'])->name('billing.index');
Route::get('/customers/lookup', [BillingController::class, 'lookupCustomer'])->name('customers.lookup');
Route::post('/billing/generate', [BillingController::class, 'generateBill'])->name('billing.generate');
Route::get('/billing/invoice/{order}', [BillingController::class, 'invoice'])->name('billing.invoice');

Route::get('/orders', [BillingController::class, 'ordersList'])->name('billing.orders.index');
Route::get('/orders/{order}/edit-data', [BillingController::class, 'editOrderData'])->name('billing.orders.editData');
Route::put('/orders/{order}', [BillingController::class, 'updateOrder'])->name('billing.orders.update');
Route::delete('/orders/{order}', [BillingController::class, 'destroyOrder'])->name('billing.orders.destroy');

Route::prefix('customers')->name('customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::post('/', [CustomerController::class, 'store'])->name('store');
    Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
    Route::get('/{customer}/edit-data', [CustomerController::class, 'editData'])->name('editData');
    Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
    Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
    Route::post('/{customer}/payments', [CustomerController::class, 'recordPayment'])->name('payments.store');
});

Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::post('/', [ProductController::class, 'store'])->name('store');
    Route::get('/{product}/edit-data', [ProductController::class, 'editData'])->name('editData');
    Route::put('/{product}', [ProductController::class, 'update'])->name('update');
    Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
});