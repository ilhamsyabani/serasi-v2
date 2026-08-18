@extends('layouts.pemohon')

@section('title', 'Upload Revisi')

@section('content')
{{-- Back Button --}}
<div class="mb-4">
    <a href="{{ route('pemohon.permohonan.show', $permohonan) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 transition-colors">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali
    </a>
</div>

{{-- Header --}}
<x-ui.card class="mb-4">
    <x-ui.card-content>
        <div class="flex items-start justify-between">
            <div>
                <p class="font-mono text-sm font-semibold text-slate-900">{{ $permohonan->no_registrasi }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $permohonan->nama_pbf_snapshot }}</p>
            </div>
            <x-ui.status-badge :status="$permohonan->status_saat_ini" />
        </div>
    </x-ui.card-content>
</x-ui.card>

{{-- Catatan Revisi --}}
@if($evaluasiTerakhir)
    <x-ui.card class="mb-4 border-l-4 border-l-amber-500">
        <x-ui.card-header title="Catatan dari Staff Sertifikasi" description="Perbaiki hal-hal berikut" />
        <x-ui.card-content>
            <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $evaluasiTerakhir->catatan ?: 'Tidak ada catatan spesifik. Mohon perbaiki dokumen sesuai standar.' }}</p>
        </x-ui.card-content>
    </x-ui.card>
@endif

{{-- Upload Form --}}
<x-ui.card>
    <x-ui.card-header title="Upload Dokumen Revisi" description="Unggah dokumen yang telah diperbaiki sesuai catatan" />
    <x-ui.card-content>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-5">
            <p class="text-sm text-blue-800">
                <strong>Panduan Upload:</strong>
            </p>
            <ul class="text-xs text-blue-700 mt-2 space-y-1 list-disc list-inside">
                <li>Upload dokumen yang diminta untuk diperbaiki</li>
                <li>Format: PDF, JPG, PNG (maks. 10MB per file)</li>
                <li>Setelah upload, permohonan akan ditinjau ulang oleh Staff</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('pemohon.revisi.store', $permohonan) }}" enctype="multipart/form-data" class="space-y-5" x-data="{
            files: [],
            error: '',
            maxKb: 10240,
            exts: ['pdf','jpg','jpeg','png'],
            maxMb: 10,
            inputError: '',

            addFiles(newFiles) {
                for (const f of newFiles) {
                    const ext = f.name.split('.').pop().toLowerCase();
                    if (this.exts.length && !this.exts.includes(ext)) {
                        this.inputError = 'Format harus ' + this.exts.join(', ').toUpperCase() + '.';
                        return;
                    }
                    if (f.size / 1024 > this.maxKb) {
                        this.inputError = 'Ukuran maksimal ' + this.maxMb + ' MB.';
                        return;
                    }
                    const exists = this.files.some(ef => ef.name === f.name && ef.size === f.size);
                    if (!exists) {
                        this.files.push(f);
                    }
                }
                this.inputError = '';
                this.syncFileInputs();
            },

            removeFile(index) {
                this.files.splice(index, 1);
                this.syncFileInputs();
            },

            syncFileInputs() {
                const container = document.getElementById('file-inputs');
                container.innerHTML = '';
                const dt = new DataTransfer();
                this.files.forEach(f => dt.items.add(f));
                const input = document.createElement('input');
                input.type = 'file';
                input.name = 'dokumen[]';
                input.multiple = true;
                input.accept = '.pdf,.jpg,.jpeg,.png';
                input.files = dt.files;
                container.appendChild(input);
            },

            get totalSize() {
                return (this.files.reduce((s, f) => s + f.size, 0) / (1024 * 1024)).toFixed(2);
            }
        }"
        @submit.prevent="
            if (!files.length) {
                error = 'Pilih minimal satu file.';
                return;
            }
            $el.submit();
        ">
            @csrf

            {{-- Container for actual file inputs (managed by Alpine via DataTransfer) --}}
            <div id="file-inputs" class="hidden"></div>

            {{-- File Input Area --}}
            <div>
                <label class="text-sm font-medium text-slate-700">
                    Pilih Dokumen Revisi <span class="text-red-500">*</span>
                </label>

                {{-- Drop zone --}}
                <div class="border-2 border-dashed rounded-lg p-5 text-center transition-colors cursor-pointer mt-1.5 mb-3"
                     :class="inputError || error ? 'border-red-400 bg-red-50/40' : (files.length ? 'border-emerald-500 bg-emerald-50/40' : 'border-slate-300 hover:border-emerald-600')"
                     @click="$refs.fileInput.click()"
                     @dragover.prevent="$event.dataTransfer.dropEffect = 'copy'"
                     @drop.prevent="addFiles($event.dataTransfer.files); $refs.fileInput.value = ''">

                    <input type="file"
                           x-ref="fileInput"
                           multiple
                           accept=".pdf,.jpg,.jpeg,.png"
                           class="hidden"
                           @change="addFiles($event.target.files); $refs.fileInput.value = ''">

                    <div x-show="files.length === 0" class="text-slate-400">
                        <svg class="mx-auto h-7 w-7 mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <p class="text-sm">Klik atau seret berkas ke sini</p>
                        <p class="text-xs mt-1">PDF, JPG, PNG &middot; maks 10 MB per file</p>
                    </div>
                    <div x-show="files.length > 0" x-cloak class="text-emerald-700">
                        <svg class="mx-auto h-6 w-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <p class="text-sm font-medium">Klik untuk menambah file lagi</p>
                    </div>
                </div>

                <p class="text-xs text-slate-400">Format: PDF, JPG, PNG. Maks. 10MB per file.</p>
                <p class="text-xs text-red-600" x-show="inputError" x-text="inputError" x-cloak></p>
            </div>

            {{-- File List --}}
            <div x-show="files.length > 0" x-cloak>
                <label class="text-sm font-medium text-slate-700 mb-2 block">
                    File Terpilih <span class="text-slate-400 font-normal" x-text="'(' + files.length + ' file, ' + totalSize + ' MB)'"></span>
                </label>
                <div class="space-y-2">
                    <template x-for="(file, index) in files" :key="index">
                        <div class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <i class="ph ph-file-pdf text-red-500 shrink-0" x-show="file.name.endsWith('.pdf') || file.name.endsWith('.PDF')"></i>
                                <i class="ph ph-image text-blue-500 shrink-0" x-show="!(file.name.endsWith('.pdf') || file.name.endsWith('.PDF'))"></i>
                                <span class="text-sm text-slate-700 truncate" x-text="file.name" :title="file.name"></span>
                                <span class="text-xs text-slate-400 shrink-0" x-text="(file.size / (1024 * 1024)).toFixed(2) + ' MB'"></span>
                            </div>
                            <button type="button" @click="removeFile(index)" class="text-slate-400 hover:text-red-600 transition-colors shrink-0 ml-2" title="Hapus file">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Alpine client error / Laravel server error --}}
            <p class="text-xs text-red-600" x-show="error" x-text="error" x-cloak></p>
            @error('dokumen')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
            @error('dokumen.*')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror

            <x-ui.button type="submit" variant="default" class="w-full sm:w-auto">
                📤 Kirim Revisi <span x-show="files.length" x-text="'(' + files.length + ' file)'"></span>
            </x-ui.button>
        </form>
    </x-ui.card-content>
</x-ui.card>
@endsection
