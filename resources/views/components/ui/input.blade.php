{{-- Form Input Shadcn-style --}}
@props([
    'label' => '',
    'type' => 'text',
    'name' => '',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'error' => '',
    'help' => '',
    'toggle' => false,
])
<div class="space-y-1.5">
    @if($label)
        <label for="{{ $name }}" class="text-sm font-medium text-slate-700">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    @if($toggle && $type === 'password')
        <div class="relative" x-data="{ show: false }">
            <input
                :type="show ? 'text' : 'password'"
                name="{{ $name }}"
                id="{{ $name }}"
                value="{{ $value }}"
                placeholder="{{ $placeholder }}"
                {{ $required ? 'required' : '' }}
                {{ $attributes->class([
                    'flex h-10 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm pr-10',
                    'placeholder:text-slate-400',
                    'focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500',
                    'disabled:cursor-not-allowed disabled:opacity-50',
                    'file:border-0 file:bg-transparent file:text-sm file:font-medium',
                    $error ? 'border-red-500 focus:ring-red-500' : '',
                ]) }}
            />
            <button type="button" @click="show = !show" class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors" aria-label="Tampilkan sembunyikan password">
                <i x-show="!show" class="ph ph-eye text-base" aria-hidden="true"></i>
                <i x-show="show" x-cloak class="ph ph-eye-slash text-base" aria-hidden="true"></i>
            </button>
        </div>
    @else
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ $value }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->class([
                'flex h-10 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm',
                'placeholder:text-slate-400',
                'focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500',
                'disabled:cursor-not-allowed disabled:opacity-50',
                'file:border-0 file:bg-transparent file:text-sm file:font-medium',
                $error ? 'border-red-500 focus:ring-red-500' : '',
            ]) }}
        />
    @endif
    @if($error)
        <p class="text-xs text-red-600">{{ $error }}</p>
    @endif
    @if($help)
        <p class="text-xs text-slate-400">{{ $help }}</p>
    @endif
</div>
