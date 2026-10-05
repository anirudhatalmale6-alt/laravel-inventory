<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $product->name }}</h2>
                <p class="font-mono text-xs text-gray-500">{{ $product->sku }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('products.edit', $product) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Edit</a>
                <a href="{{ route('products.index') }}" class="text-sm text-gray-600 hover:underline">Back to products</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <x-card>
                        <div class="flex flex-wrap items-end justify-between gap-4">
                            <div>
                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">In stock</div>
                                <div class="mt-1 text-4xl font-semibold text-gray-900">
                                    {{ $product->stock_on_hand }} <span class="text-lg font-normal text-gray-400">{{ $product->unit }}</span>
                                </div>
                                <div class="mt-2"><x-stock-badge :product="$product" /></div>
                            </div>
                            <dl class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
                                <dt class="text-gray-500">Category</dt>
                                <dd class="text-gray-900">{{ $product->category->name ?? 'Uncategorised' }}</dd>
                                <dt class="text-gray-500">Reorder level</dt>
                                <dd class="text-gray-900">{{ $product->reorder_level ?: 'not set' }}</dd>
                                <dt class="text-gray-500">Location</dt>
                                <dd class="text-gray-900">{{ $product->location ?: '--' }}</dd>
                                <dt class="text-gray-500">Cost / sale</dt>
                                <dd class="text-gray-900">{{ number_format((float) $product->cost_price, 2) }} / {{ number_format((float) $product->sale_price, 2) }}</dd>
                                <dt class="text-gray-500">Stock value</dt>
                                <dd class="text-gray-900">{{ number_format($product->stockValue(), 2) }}</dd>
                                <dt class="text-gray-500">Status</dt>
                                <dd class="text-gray-900">{{ $product->is_active ? 'Active' : 'Inactive' }}</dd>
                            </dl>
                        </div>
                        @if ($product->description)
                            <p class="mt-4 border-t border-gray-100 pt-4 text-sm text-gray-600">{{ $product->description }}</p>
                        @endif
                    </x-card>

                    <x-card title="Stock history" :flush="true">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3 font-medium">When</th>
                                        <th class="px-4 py-3 font-medium">Type</th>
                                        <th class="px-4 py-3 text-right font-medium">Change</th>
                                        <th class="px-4 py-3 text-right font-medium">Balance</th>
                                        <th class="px-4 py-3 font-medium">Reference</th>
                                        <th class="px-4 py-3 font-medium">By</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @forelse ($movements as $movement)
                                        <tr>
                                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $movement->created_at->format('d M Y H:i') }}</td>
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
                                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No movements yet. Record the first one on the right.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if ($movements->hasPages())
                            <div class="border-t border-gray-100 px-4 py-3">{{ $movements->links() }}</div>
                        @endif
                    </x-card>
                </div>

                <div>
                    <x-card title="Record movement">
                        <form method="POST" action="{{ route('products.movements.store', $product) }}" class="space-y-4" x-data="{ type: '{{ old('type', 'in') }}' }">
                            @csrf

                            <div>
                                <x-input-label for="type" value="What happened" />
                                <select id="type" name="type" x-model="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="in">Stock in (received)</option>
                                    <option value="out">Stock out (sold, used, lost)</option>
                                    <option value="adjustment">Adjustment (set exact level)</option>
                                </select>
                                <x-input-error :messages="$errors->get('type')" class="mt-1" />
                            </div>

                            <div>
                                <x-input-label for="quantity">
                                    <span x-show="type !== 'adjustment'">Quantity</span>
                                    <span x-show="type === 'adjustment'" x-cloak>Counted stock level</span>
                                </x-input-label>
                                <x-text-input id="quantity" name="quantity" type="number" min="0" class="mt-1 block w-full" :value="old('quantity')" required />
                                <p class="mt-1 text-xs text-gray-500" x-show="type === 'adjustment'" x-cloak>
                                    The difference against the current {{ $product->stock_on_hand }} is written to the history, so the count stays auditable.
                                </p>
                                <x-input-error :messages="$errors->get('quantity')" class="mt-1" />
                            </div>

                            <div x-show="type !== 'adjustment'">
                                <x-input-label for="reference" value="Reference (optional)" />
                                <x-text-input id="reference" name="reference" class="mt-1 block w-full" :value="old('reference')" placeholder="PO-1042, Invoice 88" />
                                <x-input-error :messages="$errors->get('reference')" class="mt-1" />
                            </div>

                            <div>
                                <x-input-label for="note" value="Note (optional)" />
                                <textarea id="note" name="note" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('note') }}</textarea>
                                <x-input-error :messages="$errors->get('note')" class="mt-1" />
                            </div>

                            <x-primary-button class="w-full justify-center">Save movement</x-primary-button>
                        </form>
                    </x-card>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
