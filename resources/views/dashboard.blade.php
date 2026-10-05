<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Dashboard</h2>
            <a href="{{ route('products.create') }}" class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                New product
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @php
                    $tiles = [
                        ['label' => 'Products', 'value' => number_format($stats['products']), 'hint' => $stats['active'].' active'],
                        ['label' => 'Units in stock', 'value' => number_format($stats['units']), 'hint' => 'across all products'],
                        ['label' => 'Stock value', 'value' => number_format($stats['stock_value'], 2), 'hint' => 'at cost price'],
                        ['label' => 'Needs attention', 'value' => number_format($stats['low_stock']), 'hint' => $stats['out_of_stock'].' out of stock'],
                    ];
                @endphp
                @foreach ($tiles as $tile)
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $tile['label'] }}</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $tile['value'] }}</div>
                        <div class="mt-1 text-xs text-gray-400">{{ $tile['hint'] }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <x-card title="Low stock" :flush="true">
                    @if ($lowStock->isEmpty())
                        <p class="p-4 text-sm text-gray-500 sm:p-6">Nothing is below its reorder level.</p>
                    @else
                        <ul class="divide-y divide-gray-100">
                            @foreach ($lowStock as $product)
                                <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                                    <div class="min-w-0">
                                        <a href="{{ route('products.show', $product) }}" class="block truncate font-medium text-gray-900 hover:underline">{{ $product->name }}</a>
                                        <div class="text-xs text-gray-500">{{ $product->sku }}@if ($product->category) &middot; {{ $product->category->name }} @endif</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-semibold text-gray-900">{{ $product->stock_on_hand }} / {{ $product->reorder_level }}</div>
                                        <x-stock-badge :product="$product" />
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
                            <a href="{{ route('products.index', ['status' => 'low']) }}" class="text-sm font-medium text-indigo-600 hover:underline">See all low stock</a>
                        </div>
                    @endif
                </x-card>

                <x-card title="Recent movements" :flush="true">
                    @if ($recent->isEmpty())
                        <p class="p-4 text-sm text-gray-500 sm:p-6">No stock movements recorded yet.</p>
                    @else
                        <ul class="divide-y divide-gray-100">
                            @foreach ($recent as $movement)
                                <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                                    <div class="min-w-0">
                                        <a href="{{ route('products.show', $movement->product) }}" class="block truncate font-medium text-gray-900 hover:underline">{{ $movement->product->name }}</a>
                                        <div class="text-xs text-gray-500">
                                            {{ $movement->typeLabel() }} &middot; {{ $movement->created_at->format('d M Y H:i') }}
                                            @if ($movement->user) &middot; {{ $movement->user->name }} @endif
                                        </div>
                                    </div>
                                    <div class="whitespace-nowrap text-sm font-semibold {{ $movement->quantity < 0 ? 'text-red-600' : 'text-green-700' }}">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
                            <a href="{{ route('movements.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">Full movement history</a>
                        </div>
                    @endif
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
