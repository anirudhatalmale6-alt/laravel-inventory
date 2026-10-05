<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit {{ $product->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <x-card>
                <form method="POST" action="{{ route('products.update', $product) }}">
                    @include('products._form')

                    <div class="mt-6 flex items-center gap-3">
                        <x-primary-button>Save changes</x-primary-button>
                        <a href="{{ route('products.show', $product) }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                    </div>
                </form>
            </x-card>

            <x-card title="Delete product">
                <p class="text-sm text-gray-600">
                    Deleting removes the product and its {{ $product->movements()->count() }} movement record(s). If you only want it out of
                    the way, untick Active instead.
                </p>
                <form method="POST" action="{{ route('products.destroy', $product) }}" class="mt-4" onsubmit="return confirm('Delete {{ addslashes($product->name) }} and its full stock history?');">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>Delete permanently</x-danger-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
