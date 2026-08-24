{{--
    Badge indikator SLA. Warna mengikuti konvensi CLAUDE.md §6:
    hijau = on-time, kuning = at-risk, merah = late. Clock-off dibedakan (amber)
    karena bukan keterlambatan staff, melainkan jeda menunggu pemohon.

    $sla = hasil App\Services\SlaCalculator::evaluasiLog()
--}}
@props(['sla' => null])

@if($sla && $sla['state'] !== \App\Services\SlaCalculator::STATE_TANPA_SLA)
    @php
        // Warna + ikon Phosphor per state (design_system.md §2 semantik & §4).
        $gaya = [
            \App\Services\SlaCalculator::STATE_ON_TIME   => ['bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'ph-check-circle'],
            \App\Services\SlaCalculator::STATE_AT_RISK   => ['bg-amber-50 text-amber-700 ring-amber-600/20', 'ph-warning-circle'],
            \App\Services\SlaCalculator::STATE_LATE      => ['bg-red-50 text-red-700 ring-red-600/20', 'ph-warning-octagon'],
            \App\Services\SlaCalculator::STATE_CLOCK_OFF => ['bg-amber-50 text-amber-700 ring-amber-600/20', 'ph-pause-circle'],
            \App\Services\SlaCalculator::STATE_SELESAI   => ['bg-slate-50 text-slate-600 ring-slate-500/20', 'ph-check'],
            \App\Services\SlaCalculator::STATE_SELESAI_LEBIH_AWAL => ['bg-emerald-50 text-emerald-700 ring-emerald-600/30', 'ph-rocket-launch'],
            \App\Services\SlaCalculator::STATE_TANPA_SLA => ['bg-gray-500 text-gray-500 ring-gray-500/20', 'ph-minus'],
        ];
        [$kelas, $ikon] = $gaya[$sla['state']] ?? $gaya[\App\Services\SlaCalculator::STATE_TANPA_SLA];
    @endphp

    <span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset $kelas"]) }}
          @if($sla['deadline']) title="Batas SLA: {{ $sla['deadline']->format('d M Y, H:i') }}" @endif>
        <i class="ph-fill {{ $ikon }} text-xs" aria-hidden="true"></i>{{ $sla['label'] }}
        @if($sla['durasi_sla'] !== null)
            <span class="opacity-60">({{ $sla['terpakai'] }}/{{ $sla['durasi_sla'] }} hk)</span>
        @endif
    </span>
@endif
