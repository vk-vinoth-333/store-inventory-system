<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Colgate Toothpaste', 'unique_code' => 'CT001', 'price_per_unit' => 50.00, 'tax_percentage' => 8, 'stock_on_hand' => 100],
            ['name' => 'Parle-G Biscuit', 'unique_code' => 'PB002', 'price_per_unit' => 10.00, 'tax_percentage' => 8, 'stock_on_hand' => 50],
            ['name' => 'Bread', 'unique_code' => 'BR003', 'price_per_unit' => 25.00, 'tax_percentage' => 5, 'stock_on_hand' => 4],
            ['name' => 'Milk 1L', 'unique_code' => 'ML004', 'price_per_unit' => 30.00, 'tax_percentage' => 5, 'stock_on_hand' => 9],
            ['name' => 'Eggs (12)', 'unique_code' => 'EG005', 'price_per_unit' => 45.00, 'tax_percentage' => 5, 'stock_on_hand' => 2],
            ['name' => 'Chocolate Bar', 'unique_code' => 'CB006', 'price_per_unit' => 15.00, 'tax_percentage' => 18, 'stock_on_hand' => 75],
        ];

        foreach ($products as $p) {
            Product::create($p);
        }
    }
}
