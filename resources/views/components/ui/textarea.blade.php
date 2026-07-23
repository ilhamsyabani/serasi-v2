{{-- Form Textarea Shadcn-style --}}
@props([
    'label' => '',
    'name' => '',
    'value' => '',
    'placeholder' => '',
    'rows' => 4,
    'required' => false,
    'error' => '',
    'help' => '',
])
<div class="space-y-1.5">
    @if($label)
        <label for="{{ $name }}" class="text-sm font-medium text-slate-700">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->class([
            'flex w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm',
            'placeholder:text-slate-400',
            'focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500',
            'disabled:cursor-not-allowed disabled:opacity-50',
            $error ? 'border-red-500 focus:ring-red-500' : '',
        ]) }}
    >{{ $value }}</textarea>
    @if($error)
        <p class="text-xs text-red-600">{{ $error }}</p>
    @endif
    @if($help)
        <p class="text-xs text-slate-400">{{ $help }}</p>
    @endif
</div>
