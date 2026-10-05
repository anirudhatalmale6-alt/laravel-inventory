<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $products = Product::query();

        $stats = [
            'products' => (clone $products)->count(),
            'active' => (clone $products)->where('is_active', true)->count(),
            'units' => (int) (clone $products)->sum('stock_on_hand'),
            'stock_value' => (float) (clone $products)->select(DB::raw('COALESCE(SUM(stock_on_hand * cost_price), 0) as v'))->value('v'),
            'low_stock' => (clone $products)->lowStock()->count(),
            'out_of_stock' => (clone $products)->where('stock_on_hand', '<=', 0)->count(),
        ];

        return view('dashboard', [
            'stats' => $stats,
            'lowStock' => Product::with('category')->lowStock()->orderBy('stock_on_hand')->limit(8)->get(),
            'recent' => StockMovement::with(['product', 'user'])->latest('id')->limit(10)->get(),
        ]);
    }
}
