<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $role->label()) &mdash; {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand navbar-dark bg-dark">
        <div class="container-fluid">
            <button
                class="btn btn-outline-light d-md-none me-2"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#dashboardSidebar"
                aria-controls="dashboardSidebar"
                aria-label="Toggle navigation menu"
            >
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand fw-semibold" href="{{ route('home') }}">{{ config('app.name') }}</a>
            <span class="navbar-text text-white-50 d-none d-md-inline">{{ $role->label() }} area</span>
            <div class="d-flex align-items-center gap-3 ms-auto">
                @include('partials.notifications-bell')
                <span class="text-white-50 small d-none d-sm-inline">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-light">Log out</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="flex-grow-1 d-flex">
        @include('partials.dashboard-sidebar')

        <main class="flex-grow-1 p-4">
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
