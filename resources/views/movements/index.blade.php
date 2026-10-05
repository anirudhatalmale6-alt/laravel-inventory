<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Stock movements</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <x-card>
                <form method="GET" action="{{ route('movements.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <x-input-label for="product" value="Product" />
                        <select id="product" name="product" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All products</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(request('product') == $product->id)>{{ $product->name }} ({{ $product->sku }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="type" value="Type" />
                        <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach (['' => 'All types', 'in' => 'Stock in', 'out' => 'Stock out', 'adjustment' => 'Adjustment'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="from" value="From" />
                        <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="request('from')" />
                    </div>
                    <div>
                        <x-input-label for="to" value="To" />
                        <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="request('to')" />
                    </div>
                    <div class="flex items-end gap-2">
                        <x-primary-button>Filter</x-primary-button>
                        <a href="{{ route('movements.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Reset</a>
                    </div>
                </form>
            </x-card>

            <x-card :flush="true">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3 font-medium">When</th>
                                <th class="px-4 py-3 font-medium">Product</th>
                                <th class="px-4 py-3 font-medium">Type</th>
                                <th class="px-4 py-3 text-right font-medium">Change</th>
                                <th class="px-4 py-3 text-right font-medium">Balance</th>
                                <th class="px-4 py-3 font-medium">Reference</th>
                                <th class="px-4 py-3 font-medium">By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($movements as $movement)
                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $movement->created_at->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('products.show', $movement->product) }}" class="font-medium text-gray-900 hover:underline">{{ $movement->product->name }}</a>
                                        <div class="font-mono text-xs text-gray-500">{{ $movement->product->sku }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ $movement->typeLabel() }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold {{ $movement->quantity < 0 ? 'text-red-600' : 'text-green-700' }}">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-900">{{ $movement->balance_after }}</td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $movement->reference ?: '--' }}
                                        @if ($movement->note)
                                            <div class="text-xs text-gray-400">{{ $movement->note }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $movement->user->name ?? '--' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No movements match this view.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($movements->hasPages())
                    <div class="border-t border-gray-100 px-4 py-3">{{ $movements->links() }}</div>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
