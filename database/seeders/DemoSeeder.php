<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Sample catalogue so the app is not an empty shell on first run.
 * Safe to skip in production: php artisan migrate --seed only calls DatabaseSeeder.
 */
class DemoSeeder extends Seeder
{
    public function run(StockService $stock): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => 'Demo User', 'password' => Hash::make('password')]
        );

        $categories = collect(['Fasteners', 'Packaging', 'Tools', 'Safety gear'])
            ->mapWithKeys(fn ($name) => [$name => Category::firstOrCreate(['name' => $name])]);

        $catalogue = [
            ['SKU-1001', 'M6 hex bolt 40mm', 'Fasteners', 'pcs', 0.35, 0.90, 200, 100, 'Bin A1'],
            ['SKU-1002', 'M6 nylon lock nut', 'Fasteners', 'pcs', 0.12, 0.40, 60, 150, 'Bin A2'],
            ['SKU-1003', 'Washer 6mm zinc', 'Fasteners', 'pcs', 0.05, 0.20, 0, 200, 'Bin A3'],
            ['SKU-2001', 'Cardboard box 300x200x150', 'Packaging', 'pcs', 0.85, 2.20, 420, 100, 'Rack B1'],
            ['SKU-2002', 'Packing tape 48mm clear', 'Packaging', 'roll', 1.40, 3.50, 36, 24, 'Rack B2'],
            ['SKU-2003', 'Bubble wrap 500mm x 50m', 'Packaging', 'roll', 9.90, 22.00, 7, 10, 'Rack B3'],
            ['SKU-3001', 'Cordless drill 18V', 'Tools', 'pcs', 72.00, 149.00, 12, 4, 'Cage C1'],
            ['SKU-3002', 'Drill bit set HSS 19pc', 'Tools', 'box', 14.50, 32.00, 9, 5, 'Cage C2'],
            ['SKU-3003', 'Torque wrench 1/2in', 'Tools', 'pcs', 48.00, 99.00, 3, 2, 'Cage C3'],
            ['SKU-4001', 'Safety glasses clear', 'Safety gear', 'pcs', 2.10, 6.50, 85, 40, 'Shelf D1'],
            ['SKU-4002', 'Nitrile gloves size L', 'Safety gear', 'box', 6.80, 14.00, 18, 20, 'Shelf D1'],
            ['SKU-4003', 'Hi-vis vest XL', 'Safety gear', 'pcs', 4.40, 11.00, 0, 15, 'Shelf D2'],
        ];

        foreach ($catalogue as [$sku, $name, $category, $unit, $cost, $sale, $opening, $reorder, $location]) {
            $product = Product::firstOrCreate(['sku' => $sku], [
                'name' => $name,
                'category_id' => $categories[$category]->id,
                'unit' => $unit,
                'cost_price' => $cost,
                'sale_price' => $sale,
                'reorder_level' => $reorder,
                'location' => $location,
                'is_active' => true,
            ]);

            if ($product->movements()->exists()) {
                continue;
            }

            if ($opening > 0) {
                $stock->stockIn($product, $opening, 'Opening stock', null, $user->id);
                // A couple of realistic movements so the history is not a single line.
                $stock->stockOut($product, max(1, (int) round($opening * 0.15)), 'SO-'.$product->id.'04', 'Customer order', $user->id);
                $stock->stockIn($product, max(1, (int) round($opening * 0.08)), 'PO-'.$product->id.'11', 'Restock', $user->id);
            } else {
                $stock->stockIn($product, 10, 'Opening stock', null, $user->id);
                $stock->stockOut($product, 10, 'SO-'.$product->id.'07', 'Cleared out', $user->id);
            }
        }
    }
}
