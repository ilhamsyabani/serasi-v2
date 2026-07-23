{{-- Empty State --}}
@props(['title' => 'Tidak ada data', 'description' => ''])
<div class="flex flex-col items-center justify-center py-12 text-center">
    <i class="ph ph-check-circle"></i>
    <h3 class="text-sm font-semibold text-slate-700">{{ $title }}</h3>
    @if($description)
        <p class="mt-1 text-xs text-slate-400 max-w-xs">{{ $description }}</p>
    @endif
    @if(isset($action))
        <div class="mt-4">{{ $action }}</div>
    @endif
</div>
