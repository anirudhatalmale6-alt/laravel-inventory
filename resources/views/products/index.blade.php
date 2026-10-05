<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Products</h2>
            <a href="{{ route('products.create') }}" class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                New product
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <x-card>
                <form method="GET" action="{{ route('products.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <x-input-label for="q" value="Search" />
                        <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" placeholder="Name, SKU or location" :value="request('q')" />
                    </div>
                    <div>
                        <x-input-label for="category" value="Category" />
                        <select id="category" name="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="status" value="Status" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach (['' => 'Any', 'low' => 'Low stock', 'out' => 'Out of stock', 'active' => 'Active only', 'inactive' => 'Inactive only'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <x-primary-button>Filter</x-primary-button>
                        <a href="{{ route('products.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Reset</a>
                    </div>
                </form>
            </x-card>

            <x-card :flush="true">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                @php
                                    $columns = [
                                        'sku' => 'SKU',
                                        'name' => 'Product',
                                    ];
                                @endphp
                                @foreach ($columns as $key => $label)
                                    {{-- SKU gets its own column on a wide screen and sits under the name on a phone. --}}
                                    <th @class(['px-4 py-3 font-medium', 'hidden sm:table-cell' => $key === 'sku'])>
                                        <a href="{{ route('products.index', array_merge(request()->query(), ['sort' => $key, 'direction' => $sort === $key && $direction === 'asc' ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                                            {{ $label }}
                                            @if ($sort === $key)<span>{{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif
                                        </a>
                                    </th>
                                @endforeach
                                <th class="hidden px-4 py-3 font-medium lg:table-cell">Category</th>
                                <th class="px-4 py-3 text-right font-medium">
                                    <a href="{{ route('products.index', array_merge(request()->query(), ['sort' => 'stock_on_hand', 'direction' => $sort === 'stock_on_hand' && $direction === 'asc' ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                                        In stock
                                        @if ($sort === 'stock_on_hand')<span>{{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif
                                    </a>
                                </th>
                                <th class="hidden px-4 py-3 text-right font-medium lg:table-cell">Reorder at</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="hidden px-4 py-3 text-right font-medium sm:table-cell">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($products as $product)
                                <tr class="hover:bg-gray-50">
                                    <td class="hidden whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600 sm:table-cell">{{ $product->sku }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('products.show', $product) }}" class="font-medium text-gray-900 hover:underline">{{ $product->name }}</a>
                                        @unless ($product->is_active)
                                            <span class="ms-2 rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-500">inactive</span>
                                        @endunless
                                        <div class="font-mono text-xs text-gray-500 sm:hidden">{{ $product->sku }}</div>
                                        @if ($product->location)
                                            <div class="text-xs text-gray-500">{{ $product->location }}</div>
                                        @endif
                                    </td>
                                    <td class="hidden px-4 py-3 text-gray-600 lg:table-cell">{{ $product->category->name ?? '--' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-gray-900">{{ $product->stock_on_hand }} <span class="font-normal text-gray-400">{{ $product->unit }}</span></td>
                                    <td class="hidden px-4 py-3 text-right text-gray-500 lg:table-cell">{{ $product->reorder_level ?: '--' }}</td>
                                    <td class="px-4 py-3"><x-stock-badge :product="$product" /></td>
                                    <td class="hidden whitespace-nowrap px-4 py-3 text-right sm:table-cell">
                                        <a href="{{ route('products.show', $product) }}" class="text-indigo-600 hover:underline">Open</a>
                                        <a href="{{ route('products.edit', $product) }}" class="ms-3 text-gray-600 hover:underline">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                        No products match this view.
                                        <a href="{{ route('products.create') }}" class="text-indigo-600 hover:underline">Add the first one</a>.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($products->hasPages())
                    <div class="border-t border-gray-100 px-4 py-3">{{ $products->links() }}</div>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
