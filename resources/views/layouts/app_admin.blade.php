<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css" />
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/css/adminlte.min.css') }}" />
	<link rel="icon" type="image/png" href="{{ asset('images/logo_sekolah.png') }}"/>
	<meta name="csrf-token" content="{{ csrf_token() }}" />
    @stack('css')
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">

        <!-- Header -->
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>

                </ul>
                @if (isset($navbar))
                    {{ $navbar }}
                @else
                    @include('layouts.partials.navbar')
                @endif
            </div>
        </nav>

        @include('layouts.partials.sidebar')

        <!-- Main content -->
        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <h5 class="mb-0">{{ $title ?? config('app.name') }}</h5>
                </div>
            </div>
            <div class="app-content">
                <div class="container-fluid">
                    {{ $slot }}
                </div>
            </div>
        </main>

    </div>
    <!-- Bootstrap + Popper + AdminLTE (JS) -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.min.js') }}" crossorigin="anonymous"></script>
    <script src="{{ asset('vendor/bootstrap/overlayscrollbars.browser.es6.min.js') }}" crossorigin="anonymous"></script>
    </script>
    <script src="{{ asset('vendor/adminlte/js/adminlte.min.js') }}"></script>
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @stack('js')
</body>

</html>
