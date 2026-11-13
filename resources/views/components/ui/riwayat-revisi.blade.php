{{--
    Riwayat seluruh siklus revisi sebuah permohonan: catatan evaluator + berkas
    perbaikan yang diunggah pemohon, per siklus (maks. Permohonan::MAKS_REVISI, CLAUDE.md §3 poin 2).

    Sumber data: `evaluasi` berhasil "tidak_lengkap" (satu baris per siklus, tidak
    pernah di-overwrite) dan `revisi` + `dokumen_revisi` yang menjawabnya.

    $routeDownload : nama route unduh berkas revisi (menerima id dokumen_revisi).
    $tampilkanStaff: sembunyikan nama evaluator di Portal Pelaku Usaha.
--}}
@props(['permohonan', 'routeDownload', 'tampilkanStaff' => true])

@php
    $maksRevisi = \App\Services\StatusTransitionService::MAKS_REVISI;

    $siklus = $permohonan->evaluasi
        ->where('hasil', \App\Models\Evaluasi::HASIL_TIDAK_LENGKAP)
        ->values();
@endphp

@if($siklus->isNotEmpty())
<x-ui.card {{ $attributes }}>
    <x-ui.card-header
        title="Riwayat Revisi"
        description="{{ min($siklus->count(), $maksRevisi) }} dari maks. {{ $maksRevisi }} siklus revisi — catatan evaluasi dan berkas perbaikan per siklus" />
    <x-ui.card-content class="space-y-4">
        @foreach($siklus as $i => $ev)
            @php
                $ke = $i + 1;
                // Evaluasi "tidak lengkap" setelah kuota habis = penutupan, bukan revisi ke-4.
                $penutupan = $ke > $maksRevisi;
                $revisi = $ev->revisi;
                $berkas = $revisi?->dokumenRevisi ?? collect();
            @endphp
            <div class="border border-slate-200 rounded-lg overflow-hidden">
                <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                    @if($penutupan)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                            <i class="ph ph-x-circle" aria-hidden="true"></i> Ditutup — kuota revisi habis
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                            <i class="ph ph-pencil-simple" aria-hidden="true"></i> Revisi ke-{{ $ke }}
                        </span>
                    @endif

                    @if($penutupan)
                        {{-- tidak ada unggahan yang ditunggu --}}
                    @elseif($revisi)
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700">
                            <i class="ph-fill ph-check-circle" aria-hidden="true"></i>
                            Perbaikan diunggah {{ $revisi->created_at?->format('d M Y, H:i') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700">
                            <i class="ph-fill ph-hourglass-medium" aria-hidden="true"></i>
                            Menunggu perbaikan dari pemohon
                        </span>
                    @endif
                </div>

                <div class="px-4 py-3">
                    <p class="text-xs text-slate-500">
                        Catatan evaluasi &middot; {{ $ev->tanggal_evaluasi?->format('d M Y, H:i') }}
                        @if($tampilkanStaff && $ev->staff)
                            &middot; {{ $ev->staff->nama }}
                        @endif
                    </p>
                    <p class="mt-1.5 text-sm text-slate-700 whitespace-pre-line">{{ $ev->catatan ?: 'Tidak ada catatan spesifik.' }}</p>
                </div>

                @if($berkas->isNotEmpty())
                    <ul class="divide-y divide-slate-100 border-t border-slate-200">
                        @foreach($berkas as $dr)
                            <li class="px-4 py-2.5 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-800 truncate">{{ $dr->nama_file_asli }}</p>
                                    <p class="text-xs text-slate-400">
                                        {{ number_format($dr->ukuran_file_kb, 2) }} KB
                                        @if($dr->uploaded_at)&middot; {{ $dr->uploaded_at->format('d M Y, H:i') }}@endif
                                    </p>
                                </div>
                                <x-ui.button variant="ghost" size="sm" href="{{ route($routeDownload, $dr->id) }}" target="_blank" aria-label="Unduh {{ $dr->nama_file_asli }}">
                                    <i class="ph ph-download-simple text-base" aria-hidden="true"></i>
                                </x-ui.button>
                            </li>
                        @endforeach
                    </ul>
                @elseif($revisi)
                    <p class="px-4 py-3 border-t border-slate-200 text-xs text-slate-400">Tidak ada berkas terlampir pada perbaikan ini.</p>
                @endif
            </div>
        @endforeach
    </x-ui.card-content>
</x-ui.card>
@endif
