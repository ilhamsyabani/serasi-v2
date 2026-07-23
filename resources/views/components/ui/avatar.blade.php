{{-- Avatar initials --}}
@props(['name' => '', 'size' => 'md'])
@php
$initials = collect(explode(' ', $name))->take(2)->map(fn($w) => $w[0] ?? '')->implode('');
$sizes = [
    'sm' => 'h-7 w-7 text-xs',
    'md' => 'h-9 w-9 text-sm',
    'lg' => 'h-12 w-12 text-base',
    'xl' => 'h-16 w-16 text-xl',
];
@endphp
<span class="inline-flex items-center justify-center rounded-full bg-blue-900 font-semibold text-white {{ $sizes[$size] ?? $sizes['md'] }}">
    {{ strtoupper($initials) }}
</span>
