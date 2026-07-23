{{-- Form Select Shadcn-style --}}
@props([
    'label' => '',
    'name' => '',
    'options' => [], // ['value' => 'label', ...]
    'selected' => null,
    'placeholder' => '— Pilih —',
    'required' => false,
    'error' => '',
])
<div class="space-y-1.5">
    @if($label)
        <label for="{{ $name }}" class="text-sm font-medium text-slate-700">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    <select
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->class([
            'flex h-10 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm',
            'focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500',
            'disabled:cursor-not-allowed disabled:opacity-50',
            $error ? 'border-red-500 focus:ring-red-500' : '',
        ]) }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach($options as $val => $label)
            <option value="{{ $val }}" @selected((string) $val === (string) ($selected ?? old($name)))>{{ $label }}</option>
        @endforeach
    </select>
    @if($error)
        <p class="text-xs text-red-600">{{ $error }}</p>
    @endif
</div>
