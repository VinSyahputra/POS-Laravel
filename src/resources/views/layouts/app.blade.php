<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — POS Food Court</title>
    <script>window.POS_OUTLET = @json(config('app.outlet_name'));</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-screen overflow-hidden bg-slate-100 font-sans text-slate-900 antialiased">
    @yield('content')
</body>
</html>
