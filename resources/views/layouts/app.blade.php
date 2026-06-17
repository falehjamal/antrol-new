<!DOCTYPE html>
<html lang="id" dir="ltr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - ANTROL MJKN</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('sneat/vendor/fonts/boxicons.css') }}" />
    <link rel="stylesheet" href="{{ asset('sneat/vendor/css/core.css') }}" />
    @vite(['resources/css/app.css', 'resources/js/datatables.js'])
    @stack('styles')
</head>
<body class="antrol-admin">
    @include('partials.sidebar')

    <div class="antrol-main">
        @include('partials.navbar')

        <main class="antrol-content">
            @yield('content')
        </main>

        @include('partials.footer')
    </div>

    <script src="{{ asset('sneat/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('sneat/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('sneat/vendor/js/bootstrap.js') }}"></script>
    @stack('datatable-scripts')
    @stack('scripts')
</body>
</html>
