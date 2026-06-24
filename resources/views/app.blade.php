<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>插件市场 — {{ config('app.name', 'Laravel') }}</title>
    @if(app()->environment('local'))
        @vite(['resources/frontend/src/main.ts'], 'plugins/plugin-store')
    @else
        <link rel="stylesheet" href="{{ asset('plugins/plugin-store/assets/index.css') }}">
    @endif
</head>
<body>
    <div id="app"></div>
    <script>
        window.__PLUGIN_STORE_CONFIG__ = {
            apiBase: '/api/plugin-store',
            csrfToken: '{{ csrf_token() }}',
            user: @json(auth()->user()?->only('id', 'name')),
        };
    </script>
    @if(!app()->environment('local'))
        <script type="module" src="{{ asset('plugins/plugin-store/assets/index.js') }}"></script>
    @endif
</body>
</html>
