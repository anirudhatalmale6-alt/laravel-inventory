<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Categories</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <x-card title="Add a category">
                <form method="POST" action="{{ route('categories.store') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="grow">
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                    </div>
                    <x-primary-button>Add</x-primary-button>
                </form>
            </x-card>

            <x-card :flush="true">
                <ul class="divide-y divide-gray-100">
                    @forelse ($categories as $category)
                        <li class="flex flex-wrap items-center gap-3 px-4 py-3 sm:px-6">
                            <form method="POST" action="{{ route('categories.update', $category) }}" class="flex grow items-center gap-2">
                                @csrf
                                @method('PUT')
                                <x-text-input name="name" class="block w-full" :value="$category->name" required />
                                <x-secondary-button type="submit">Save</x-secondary-button>
                            </form>
                            <span class="whitespace-nowrap text-sm text-gray-500">{{ $category->products_count }} product(s)</span>
                            <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Delete this category? Its products stay but become uncategorised.');">
                                @csrf
                                @method('DELETE')
                                <x-danger-button>Delete</x-danger-button>
                            </form>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-gray-500 sm:px-6">No categories yet. They are optional.</li>
                    @endforelse
                </ul>
            </x-card>
        </div>
    </div>
</x-app-layout>
