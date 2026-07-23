{{-- Card Header --}}
@props(['title' => '', 'description' => ''])
<div class="flex flex-col space-y-1.5 p-6 pb-4">
    @if($title)
        <h3 class="font-semibold text-lg leading-none tracking-tight text-blue-900">{{ $title }}</h3>
    @endif
    @if($description)
        <p class="text-sm text-slate-500">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
