<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StockMovementController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request): View
    {
        $movements = StockMovement::with(['product', 'user'])
            ->when($request->filled('product'), fn ($q) => $q->where('product_id', $request->query('product')))
            ->when(in_array($request->query('type'), ['in', 'out', 'adjustment'], true),
                fn ($q) => $q->where('type', $request->query('type')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('to')))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('movements.index', [
            'movements' => $movements,
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku']),
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:in,out,adjustment'],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $reference = $data['reference'] ?? null;
        $note = $data['note'] ?? null;
        $userId = $request->user()?->id;

        try {
            $movement = match ($data['type']) {
                'in' => $this->stock->stockIn($product, $data['quantity'], $reference, $note, $userId),
                'out' => $this->stock->stockOut($product, $data['quantity'], $reference, $note, $userId),
                'adjustment' => $this->stock->adjustTo($product, $data['quantity'], $note, $userId),
            };
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['quantity' => $e->getMessage()]);
        }

        $message = $movement === null
            ? 'Stock was already at that level, nothing recorded.'
            : "Stock updated, {$product->name} now at {$product->fresh()->stock_on_hand} {$product->unit}.";

        return redirect()->route('products.show', $product)->with('status', $message);
    }
}
