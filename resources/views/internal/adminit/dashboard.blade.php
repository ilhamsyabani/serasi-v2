@extends('layouts.internal')

@section('title', 'Dashboard Admin IT')
@section('content')
<?php $pageTitle = 'Dashboard'; ?>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <x-ui.stat-card :value="$stats['totalUsers']" label="User Aktif" description="Akun internal" icon="ph-users-three" />
    <x-ui.stat-card :value="$stats['totalPbf']" label="PBF Terdaftar" description="Akun pemohon" icon="ph-buildings" />
    <x-ui.stat-card :value="$stats['totalPermohonan']" label="Permohonan" description="Total permohonan" icon="ph-files" />
    <x-ui.stat-card :value="$stats['notifikasiGagal']" label="Notifikasi Gagal" description="Butuh perhatian" icon="ph-warning-circle" />
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <x-ui.card>
        <x-ui.card-header title="Manajemen User" description="Kelola akun internal BBPOM" />
        <x-ui.card-content>
            <x-ui.button variant="default" href="{{ route('internal.adminit.users.index') }}">
                Kelola User & Role →
            </x-ui.button>
        </x-ui.card-content>
    </x-ui.card>
    <x-ui.card>
        <x-ui.card-header title="Konfigurasi Sistem" description="SLA, hari libur, template" />
        <x-ui.card-content class="space-y-2">
            <x-ui.button variant="default" href="{{ route('internal.adminit.hari-libur.index') }}" class="w-full justify-start">
                <i class="ph ph-calendar-x mr-2" aria-hidden="true"></i>
                Hari Libur & Cuti →
            </x-ui.button>
        </x-ui.card-content>
    </x-ui.card>
</div>
@endsection
