@props(['title' => null, 'flush' => false])

<div {{ $attributes->merge(['class' => 'overflow-hidden bg-white shadow-sm sm:rounded-lg']) }}>
    @if ($title)
        <div class="border-b border-gray-100 px-4 py-3 sm:px-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">{{ $title }}</h2>
        </div>
    @endif
    <div @class(['p-4 sm:p-6' => ! $flush])>
        {{ $slot }}
    </div>
</div>
