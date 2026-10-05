<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">New product</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <x-card>
                <form method="POST" action="{{ route('products.store') }}">
                    @include('products._form')

                    <div class="mt-6 flex items-center gap-3">
                        <x-primary-button>Create product</x-primary-button>
                        <a href="{{ route('products.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
