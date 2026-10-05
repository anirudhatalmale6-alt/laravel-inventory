<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request): View
    {
        $sorts = ['name', 'sku', 'stock_on_hand', 'updated_at'];
        $sort = in_array($request->query('sort'), $sorts, true) ? $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $products = Product::with('category')
            ->search($request->query('q'))
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->query('category')))
            ->when($request->query('status') === 'low', fn ($q) => $q->lowStock())
            ->when($request->query('status') === 'out', fn ($q) => $q->where('stock_on_hand', '<=', 0))
            ->when($request->query('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->query('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(): View
    {
        return view('products.create', [
            'product' => new Product(['unit' => 'pcs', 'is_active' => true]),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->productAttributes());

        $opening = (int) ($request->validated()['opening_stock'] ?? 0);

        if ($opening > 0) {
            $this->stock->stockIn($product, $opening, 'Opening stock', null, $request->user()?->id);
        }

        return redirect()->route('products.show', $product)
            ->with('status', "{$product->name} created.");
    }

    public function show(Product $product): View
    {
        $product->load('category');

        return view('products.show', [
            'product' => $product,
            'movements' => $product->movements()->with('user')->paginate(15),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->productAttributes());

        return redirect()->route('products.show', $product)
            ->with('status', "{$product->name} updated.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name;
        $product->delete();

        return redirect()->route('products.index')->with('status', "{$name} deleted.");
    }
}
