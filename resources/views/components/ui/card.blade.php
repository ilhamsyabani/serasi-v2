{{-- Card wrapper Shadcn-style --}}
@props(['class' => ''])
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm {{ $class }}">
    {{ $slot }}
</div>
