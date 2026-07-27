{{-- Logo component — fleksibel, bisa isi img dari public atau fallback icon+text --}}
@props([
    'src' => null,
    'alt' => 'Logo',
    'text' => null,
    'subtext' => null,
    'href' => '/',
    'imgClass' => 'h-9 w-9',
    'wrapperClass' => '',
])

<a href="{{ $href }}" class="flex items-center gap-3 {{ $wrapperClass }}">
    @if($src)
        <img src="{{ asset($src) }}" alt="{{ $alt }}" class="{{ $imgClass }} object-contain" loading="lazy" />
    @else
        <div class="h-9 w-9 rounded-xl bg-emerald-500 flex items-center justify-center text-white shrink-0">
            <i class="ph-fill ph-first-aid-kit text-lg" aria-hidden="true"></i>
        </div>
    @endif

    @if($text)
        <div>
            <p class="text-sm font-bold text-blue-900 leading-none">{{ $text }}</p>
            @if($subtext)
                <p class="text-[10px] text-slate-400 leading-none mt-0.5">{{ $subtext }}</p>
            @endif
        </div>
    @endif
</a>
