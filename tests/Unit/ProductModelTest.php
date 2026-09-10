<?php

namespace Tests\Unit;

use App\Models\Product;
use PHPUnit\Framework\TestCase;

class ProductModelTest extends TestCase
{
    public function test_has_stock_returns_true_when_enough(): void
    {
        $product = new Product(['stock_on_hand' => 5]);
        $this->assertTrue($product->hasStock(5));
        $this->assertTrue($product->hasStock(3));
    }

    public function test_has_stock_returns_false_when_insufficient(): void
    {
        $product = new Product(['stock_on_hand' => 2]);
        $this->assertFalse($product->hasStock(3));
    }
}
