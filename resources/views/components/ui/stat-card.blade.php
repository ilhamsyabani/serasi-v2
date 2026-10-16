{{--
    KPI Stat Card (design_system.md §5.1).
    `icon` menerima nama Phosphor (mis. "ph-files") ATAU emoji — keduanya didukung
    agar kompatibel dengan pemanggil lama.
--}}
@props(['value' => 0, 'label' => '', 'description' => '', 'icon' => '', 'trend' => null, 'color' => 'emerald'])

@php
    $isPhosphor = is_string($icon) && (str_starts_with($icon, 'ph-') || str_starts_with($icon, 'ph '));
    $iconClasses = $isPhosphor ? (str_starts_with($icon, 'ph ') ? $icon : 'ph ' . $icon) : '';

    $colorClasses = match ($color) {
        'slate' => 'bg-slate-100 text-slate-700',
        'blue' => 'bg-blue-50 text-blue-600',
        'emerald', 'green' => 'bg-emerald-50 text-emerald-600',
        'red' => 'bg-red-50 text-red-600',
        'amber', 'yellow' => 'bg-amber-50 text-amber-600',
        'purple' => 'bg-purple-50 text-purple-600',
        default => 'bg-emerald-50 text-emerald-600',
    };
@endphp

<div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-blue-900">{{ $value }}</p>
        </div>
        @if($icon)
            <div class="rounded-xl {{ $colorClasses }} p-2.5 shrink-0">
                @if($isPhosphor)
                    <i class="{{ $iconClasses }} text-xl" aria-hidden="true"></i>
                @else
                    <span class="text-xl leading-none">{{ $icon }}</span>
                @endif
            </div>
        @endif
    </div>
    @if($description)
        <p class="mt-2 text-xs text-slate-400">{{ $description }}</p>
    @endif
    @if($trend !== null)
        <p class="mt-2 text-xs font-medium {{ str_starts_with((string)$trend, '-') ? 'text-red-500' : 'text-emerald-600' }}">
            {{ $trend }}
        </p>
    @endif
</div>
