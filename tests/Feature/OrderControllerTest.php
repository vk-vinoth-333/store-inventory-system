<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Queue::fake();
    }

    public function test_it_creates_an_order_and_deducts_stock(): void
    {
        $product = Product::first();
        $initialStock = $product->stock_on_hand;

        $response = $this->postJson('/api/orders', [
            'customer_email' => 'new@example.com',
            'customer_name' => 'New Person',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.grand_total', fn($v) => is_string($v) || is_numeric($v));

        $this->assertDatabaseHas('customers', ['email' => 'new@example.com']);
        $this->assertEquals($initialStock - 2, $product->fresh()->stock_on_hand);
        Queue::assertPushed(SendOrderConfirmation::class);
    }

    public function test_it_rejects_insufficient_stock(): void
    {
        $product = Product::first();

        $response = $this->postJson('/api/orders', [
            'customer_email' => 'x@example.com',
            'customer_name' => 'X',
            'items' => [['product_id' => $product->id, 'quantity' => 99999]],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('orders', 0);
        Queue::assertNotPushed(SendOrderConfirmation::class);
    }

    public function test_it_validates_required_fields(): void
    {
        $this->postJson('/api/orders', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_email', 'customer_name', 'items']);
    }

    public function test_it_fetches_order_history_by_email(): void
    {
        $customer = Customer::first();

        $response = $this->getJson("/api/customers/{$customer->email}/orders");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('customer.email', $customer->email);
    }

    public function test_it_returns_404_for_unknown_customer(): void
    {
        $this->getJson('/api/customers/nobody@example.com/orders')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_it_returns_low_stock_products(): void
    {
        $response = $this->getJson('/api/products/low-stock?threshold=5');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('threshold', 5);

        foreach ($response->json('products') as $p) {
            $this->assertLessThan(5, $p['stock_on_hand']);
        }
    }

   
    public function test_concurrent_orders_do_not_oversell(): void
    {
        $product = Product::create([
            'name' => 'Last Item',
            'unique_code' => 'LAST001',
            'price_per_unit' => 100,
            'tax_percentage' => 0,
            'stock_on_hand' => 1,
        ]);

        $payload = [
            'customer_email' => 'race@example.com',
            'customer_name' => 'Racer',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('/api/orders', $payload);
        }

        $successCount = collect($responses)->filter(fn($r) => $r->status() === 201)->count();
        $failCount = collect($responses)->filter(fn($r) => $r->status() === 422)->count();

        $this->assertEquals(1, $successCount, 'Only one order should succeed');
        $this->assertEquals(2, $failCount, 'Two orders should fail cleanly');
        $this->assertEquals(0, $product->fresh()->stock_on_hand);
        $this->assertDatabaseCount('orders', 1);
    }
}
