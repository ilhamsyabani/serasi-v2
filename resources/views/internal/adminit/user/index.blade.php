@extends('layouts.internal')

@section('title', 'Manajemen User')
@section('content')
<?php $pageTitle = 'Manajemen User'; ?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Manajemen User</h2>
        <p class="text-sm text-slate-500">Kelola akun internal BBPOM</p>
    </div>
    <x-ui.button variant="default" href="{{ route('internal.adminit.users.create') }}">+ Tambah User</x-ui.button>
</div>

<x-ui.card>
    <x-ui.card-content class="p-0">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">NIP</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Nama</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($users as $u)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $u->nip }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <x-ui.avatar :name="$u->nama" size="sm" />
                            <span class="font-medium text-slate-900">{{ $u->nama }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $u->email }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                            @if($u->role?->kode === 'kepala_balai') bg-blue-100 text-blue-800
                            @elseif($u->role?->kode === 'ketua_tim') bg-purple-100 text-purple-800
                            @elseif($u->role?->kode === 'staff_sertifikasi') bg-cyan-100 text-cyan-800
                            @else bg-slate-100 text-slate-800 @endif">
                            {{ $u->role?->nama ?? '-' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($u->is_aktif)
                            <span class="inline-flex items-center gap-1 text-xs text-emerald-600"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Aktif</span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs text-slate-400"><span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <x-ui.button variant="ghost" size="sm" href="{{ route('internal.adminit.users.edit', $u) }}">Edit</x-ui.button>
                        <form action="{{ route('internal.adminit.users.destroy', $u) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <x-ui.button type="submit" variant="ghost" size="sm" onclick="return confirm('Hapus user ini?')" class="!text-red-600 hover:!bg-red-50">Hapus</x-ui.button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6"><x-ui.empty-state title="Belum ada user" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </x-ui.card-content>
</x-ui.card>
@endsection
