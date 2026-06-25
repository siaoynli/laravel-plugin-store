<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>插件市场 — {{ config('app.name', 'Laravel') }}</title>
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
    <script type="module" src="{{ asset('plugins/siaoynli-plugin-store/index.js') }}"></script>
</body>
</html>
