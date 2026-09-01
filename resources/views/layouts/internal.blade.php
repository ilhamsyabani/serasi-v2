<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ Vite::asset('resources/css/app.css') }}">
    <script type="module" src="{{ Vite::asset('resources/js/app.js') }}" defer></script>
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
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Flash Messages (SweetAlert2) --}}
    @if(session('success'))
        <script>document.addEventListener('DOMContentLoaded',function(){if(window.Swal){Swal.fire({icon:'success',title:'Berhasil',text:{!! json_encode(session('success')) !!},confirmButtonColor:'#059669',timer:4000,timerProgressBar:true})}});</script>
    @endif
    @if(session('error'))
        <script>document.addEventListener('DOMContentLoaded',function(){if(window.Swal){Swal.fire({icon:'error',title:'Gagal',text:{!! json_encode(session('error')) !!},confirmButtonColor:'#dc2626',timer:5000,timerProgressBar:true})}});</script>
    @endif
    @if(session('warning'))
        <script>document.addEventListener('DOMContentLoaded',function(){if(window.Swal){Swal.fire({icon:'warning',title:'Perhatian',text:{!! json_encode(session('warning')) !!},confirmButtonColor:'#d97706',timer:6000,timerProgressBar:true})}});</script>
    @endif
    @if($errors->any())
        <script>document.addEventListener('DOMContentLoaded',function(){if(window.Swal){Swal.fire({icon:'error',title:'Validasi Gagal',text:{!! json_encode($errors->first()) !!},confirmButtonColor:'#dc2626'})}});</script>
    @endif

    @stack('scripts')
</body>
</html>
