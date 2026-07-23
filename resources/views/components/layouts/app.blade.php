<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Aplikasi') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.head-assets')
    @stack('head')
</head>
<body class="antialiased">
    @yield('body')
    @stack('scripts')
</body>
</html>
