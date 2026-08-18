<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.head-assets')
</head>
<body class="bg-slate-50 antialiased">
    <div class="flex h-screen overflow-hidden">
        {{-- Sidebar --}}
        <x-layouts.sidebar />

        {{-- Main Area --}}
        <div class="flex flex-1 flex-col overflow-hidden">
            {{-- Topbar --}}
            <x-layouts.topbar title="{{ $pageTitle ?? '' }}" />

            {{-- Content --}}
            <main class="flex-1 overflow-y-auto p-6">
                {{-- Flash Messages --}}
                @if(session('success'))
                    <x-ui.alert type="success" class="mb-4" dismissible>{{ session('success') }}</x-ui.alert>
                @endif
                @if(session('error'))
                    <x-ui.alert type="error" class="mb-4" dismissible>{{ session('error') }}</x-ui.alert>
                @endif
                @if($errors->any())
                    <div x-data x-init="
                        Swal.fire({
                            icon: 'error',
                            title: 'Validasi Gagal',
                            text: @js($errors->first()),
                            confirmButtonColor: '#166534'
                        });
                    "></div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
