<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GASQ')</title>
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/gasq-theme.css') }}" rel="stylesheet">
</head>
<body class="bg-gasq-background min-vh-100 d-flex flex-column">
    <div class="d-flex flex-grow-1 align-items-center justify-content-center py-5 gasq-hero-bg">
        <div class="container container-tight">
            <div class="text-center mb-4">
                <a href="{{ url('/') }}" class="d-inline-block">
                    <x-logo height="64" />
                </a>
            </div>
            @yield('content')
        </div>
    </div>
    <script src="{{ mix('js/app.js') }}"></script>
</body>
</html>
