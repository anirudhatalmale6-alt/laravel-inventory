@csrf
@if ($product->exists)
    @method('PUT')
@endif

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="sku" value="SKU" />
        <x-text-input id="sku" name="sku" class="mt-1 block w-full" :value="old('sku', $product->sku)" required />
        <x-input-error :messages="$errors->get('sku')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="name" value="Product name" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $product->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="description" value="Description (optional)" />
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $product->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="category_id" value="Category" />
        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Uncategorised</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="unit" value="Unit" />
        <x-text-input id="unit" name="unit" class="mt-1 block w-full" :value="old('unit', $product->unit ?? 'pcs')" placeholder="pcs, kg, box" required />
        <x-input-error :messages="$errors->get('unit')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="cost_price" value="Cost price" />
        <x-text-input id="cost_price" name="cost_price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('cost_price', $product->cost_price ?? '0.00')" required />
        <x-input-error :messages="$errors->get('cost_price')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="sale_price" value="Sale price" />
        <x-text-input id="sale_price" name="sale_price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('sale_price', $product->sale_price ?? '0.00')" required />
        <x-input-error :messages="$errors->get('sale_price')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="reorder_level" value="Reorder level" />
        <x-text-input id="reorder_level" name="reorder_level" type="number" min="0" class="mt-1 block w-full" :value="old('reorder_level', $product->reorder_level ?? 0)" required />
        <p class="mt-1 text-xs text-gray-500">Flagged as low stock at or below this figure. Use 0 to never flag it.</p>
        <x-input-error :messages="$errors->get('reorder_level')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="location" value="Location (optional)" />
        <x-text-input id="location" name="location" class="mt-1 block w-full" :value="old('location', $product->location)" placeholder="Shelf A3, Warehouse 2" />
        <x-input-error :messages="$errors->get('location')" class="mt-1" />
    </div>

    @unless ($product->exists)
        <div>
            <x-input-label for="opening_stock" value="Opening stock (optional)" />
            <x-text-input id="opening_stock" name="opening_stock" type="number" min="0" class="mt-1 block w-full" :value="old('opening_stock', 0)" />
            <p class="mt-1 text-xs text-gray-500">Recorded as the first stock-in so the history starts clean.</p>
            <x-input-error :messages="$errors->get('opening_stock')" class="mt-1" />
        </div>
    @endunless

    <div class="sm:col-span-2">
        <label class="inline-flex items-center">
            {{-- The hidden 0 means an unticked box still posts a value, so "active" can be turned off. --}}
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked(old('is_active', $product->exists ? $product->is_active : true))>
            <span class="ms-2 text-sm text-gray-700">Active (shown as available for stock movements)</span>
        </label>
    </div>
</div>
