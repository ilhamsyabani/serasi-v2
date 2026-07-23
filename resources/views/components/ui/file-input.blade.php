{{--
    File Upload Input dengan validasi client-side (Alpine).

    Validasi ekstensi & ukuran dilakukan di sini DAN di server (FormRequest) —
    CLAUDE.md §6 melarang mengandalkan salah satu saja. `maxKb` & `accept`
    sebaiknya diisi dari konstanta DokumenPermohonan agar konsisten.
--}}
@props([
    'label' => '',
    'name' => '',
    'multiple' => false,
    'accept' => '',
    'help' => '',
    'required' => false,
    'maxKb' => \App\Models\DokumenPermohonan::UKURAN_MAKS_KB,
])

@php
    $maxMb = round($maxKb / 1024, $maxKb % 1024 === 0 ? 0 : 1);
    $extList = collect(explode(',', $accept))
        ->map(fn ($e) => ltrim(trim($e), '.'))
        ->filter()
        ->values()
        ->all();
    $hasError = $errors->has($name);
@endphp

<div class="space-y-1.5"
     x-data="{
        error: '',
        fileName: '',
        maxKb: {{ (int) $maxKb }},
        exts: @js($extList),
        handle(files) {
            this.error = '';
            this.fileName = '';
            if (! files || files.length === 0) return;
            const f = files[0];
            const ext = f.name.split('.').pop().toLowerCase();
            if (this.exts.length && ! this.exts.includes(ext)) {
                this.error = 'Format harus ' + this.exts.join(', ').toUpperCase() + '.';
                this.$refs.input.value = '';
                return;
            }
            if (f.size / 1024 > this.maxKb) {
                this.error = 'Ukuran maksimal {{ $maxMb }} MB.';
                this.$refs.input.value = '';
                return;
            }
            this.fileName = f.name;
        }
     }">
    @if($label)
        <label for="{{ $name }}" class="text-sm font-medium text-slate-700">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif

    <div class="border-2 border-dashed rounded-lg p-5 text-center transition-colors cursor-pointer"
         :class="error || {{ $hasError ? 'true' : 'false' }} ? 'border-red-400 bg-red-50/40' : (fileName ? 'border-emerald-500 bg-emerald-50/40' : 'border-slate-300 hover:border-emerald-600')"
         @click="$refs.input.click()"
         @dragover.prevent="$event.dataTransfer.dropEffect = 'copy'"
         @drop.prevent="$refs.input.files = $event.dataTransfer.files; handle($event.dataTransfer.files)">
        <input type="file"
               name="{{ $name }}{{ $multiple ? '[]' : '' }}"
               id="{{ $name }}"
               {{ $multiple ? 'multiple' : '' }}
               @if($accept) accept="{{ $accept }}" @endif
               {{ $required ? 'required' : '' }}
               x-ref="input"
               class="hidden"
               @change="handle($event.target.files)">

        {{-- Keadaan kosong --}}
        <div x-show="! fileName" class="text-slate-400">
            <svg class="mx-auto h-7 w-7 mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            <p class="text-sm">Klik atau seret berkas ke sini</p>
            @if($extList)
                <p class="text-xs mt-1">{{ strtoupper(implode(', ', $extList)) }} &middot; maks {{ $maxMb }} MB</p>
            @endif
        </div>

        {{-- Keadaan terpilih --}}
        <div x-show="fileName" x-cloak class="flex items-center justify-center gap-2 text-emerald-700">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-sm font-medium truncate max-w-[16rem]" x-text="fileName"></span>
            <button type="button" @click.stop="fileName=''; error=''; $refs.input.value=''" class="text-slate-400 hover:text-red-600" aria-label="Hapus berkas">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    @if($help)
        <p class="text-xs text-slate-400" x-show="! error">{{ $help }}</p>
    @endif

    {{-- Error dari Alpine (client) ATAU dari server (FormRequest) --}}
    <p class="text-xs text-red-600" x-show="error" x-text="error" x-cloak></p>
    @error($name)
        <p class="text-xs text-red-600" x-show="! error">{{ $message }}</p>
    @enderror
</div>
