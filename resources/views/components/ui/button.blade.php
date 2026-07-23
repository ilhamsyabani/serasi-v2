{{-- Tombol aksi dengan varian Shadcn-style --}}
@props([
    'variant' => 'default', // default | outline | ghost | destructive | secondary
    'size' => 'md', // sm | md | lg | icon
    'href' => null,
    'type' => 'button',
])
@php
$base = 'inline-flex items-center justify-center font-medium rounded-xl transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40 focus-visible:ring-offset-1 disabled:bg-slate-200 disabled:text-slate-400 disabled:pointer-events-none';

$sizes = [
    'sm'   => 'h-8 px-3 text-xs gap-1.5 rounded-lg',
    'md'   => 'h-10 px-4 text-sm gap-2',
    'full'   => 'h-10 px-4 text-sm gap-2 w-full',
    'lg'   => 'h-12 px-6 text-base gap-2',
    'icon' => 'h-10 w-10',
];
$variants = [
    // Primary CTA emerald (design_system.md §5.2)
    'default'    => 'bg-emerald-500 text-white hover:bg-emerald-600 shadow-sm',
    'outline'    => 'border border-emerald-500 bg-white text-emerald-600 hover:bg-emerald-50',
    'ghost'      => 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
    'destructive'=> 'bg-red-500 text-white hover:bg-red-600 shadow-sm',
    // Secondary: latar slate-100, teks navy
    'secondary'  => 'bg-slate-100 text-blue-900 hover:bg-slate-200',
    'link'       => 'text-emerald-600 underline-offset-4 hover:underline p-0 h-auto',
];
@endphp
@if($href)
    <a href="{{ $href }}" class="{{ $base }} {{ $sizes[$size] }} {{ $variants[$variant] }} {{ $attributes->class(' ') }}">
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" class="{{ $base }} {{ $sizes[$size] }} {{ $variants[$variant] }} {{ $attributes->class(' ') }}">
        {{ $slot }}
    </button>
@endif
