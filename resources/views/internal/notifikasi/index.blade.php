@extends('layouts.internal')

@section('title', 'Notifikasi')
@section('content')
<?php $pageTitle = 'Notifikasi'; ?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Notifikasi</h2>
        <p class="text-sm text-slate-500">Riwayat notifikasi yang diterima</p>
    </div>
</div>

<x-ui.card>
    <x-ui.card-content class="p-0">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Channel</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Template</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Permohonan</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($notifikasis as $n)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 text-slate-600 text-xs whitespace-nowrap">
                        {{ $n->created_at->format('d M Y, H:i') }}
                    </td>
                    <td class="px-4 py-3">
                        <span @class([
                            'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                            'bg-blue-100 text-blue-800' => $n->channel === 'email',
                            'bg-emerald-100 text-emerald-800' => $n->channel === 'whatsapp',
                        ])>
                            @if($n->channel === 'email')
                                <i class="ph ph-envelope mr-1" aria-hidden="true"></i>Email
                            @else
                                <i class="ph ph-whatsapp-logo mr-1" aria-hidden="true"></i>WhatsApp
                            @endif
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="font-mono text-xs text-slate-700">{{ $n->template_kode ?? '-' }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($n->permohonan)
                            <a href="{{ route('internal.permohonan.show', $n->permohonan) }}"
                               class="font-mono text-xs text-blue-600 hover:underline">
                                {{ $n->permohonan->no_registrasi }}
                            </a>
                        @else
                            <span class="text-xs text-slate-400">Sistem</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($n->status_kirim === 'terkirim')
                            <span class="inline-flex items-center gap-1 text-xs text-emerald-600">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Terkirim
                            </span>
                        @elseif($n->status_kirim === 'pending')
                            <span class="inline-flex items-center gap-1 text-xs text-amber-600">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Pending
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs text-red-600">
                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>Gagal
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5"><x-ui.empty-state title="Belum ada notifikasi" description="Notifikasi akan muncul di sini." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </x-ui.card-content>
</x-ui.card>

@if($notifikasis->hasPages())
<div class="mt-4">
    {{ $notifikasis->links() }}
</div>
@endif
@endsection
