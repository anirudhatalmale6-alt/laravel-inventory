@props(['product'])

@php
    if ($product->isOutOfStock()) {
        $classes = 'bg-red-100 text-red-800';
        $label = 'Out of stock';
    } elseif ($product->isLowStock()) {
        $classes = 'bg-amber-100 text-amber-800';
        $label = 'Low stock';
    } else {
        $classes = 'bg-green-100 text-green-800';
        $label = 'In stock';
    }
@endphp

<span class="inline-flex items-center whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium {{ $classes }}">{{ $label }}</span>
