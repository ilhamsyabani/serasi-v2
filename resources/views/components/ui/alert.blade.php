{{-- Alert / Flash message Shadcn-style --}}
@props(['type' => 'info']) {{-- info | success | warning | error | destructive --}}
@php
$map = [
    'info'       => ['bg-blue-50 border-blue-200 text-blue-800', 'ℹ️'],
    'success'    => ['bg-emerald-50 border-emerald-200 text-emerald-800', '✓'],
    'warning'    => ['bg-amber-50 border-amber-200 text-amber-800', '⚠️'],
    'error'      => ['bg-red-50 border-red-200 text-red-800', '✕'],
    'destructive'=> ['bg-red-50 border-red-200 text-red-800', '✕'],
];
[$cls, $icon] = $map[$type] ?? $map['info'];
@endphp
<div class="flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {{ $cls }}" role="alert">
    <span class="mt-0.5 shrink-0 text-base">{{ $icon }}</span>
    <div class="flex-1">
        {{ $slot }}
    </div>
    @if(isset($dismissible))
        <button type="button" onclick="this.closest('[role=alert]').remove()" class="shrink-0 opacity-70 hover:opacity-100 cursor-pointer border-none bg-transparent text-lg leading-none">&times;</button>
    @endif
</div>
