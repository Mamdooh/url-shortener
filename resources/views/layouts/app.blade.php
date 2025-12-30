<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'URL Shortener') }}</title>

    <!-- Scripts -->
    @if(env('APP_ENV') === 'local')
        <script src="{{ asset('js/app.js') }}" defer></script>
    @else
        <script src="{{ secure_asset('js/app.js') }}" defer></script>
    @endif

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Style s -->
    @if(env('APP_ENV') === 'local')
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @else
        <link href="{{ secure_asset('css/app.css') }}" rel="stylesheet">
    @endif
</head>
<body>
 
    @if(request()->getHost() === 'dev.mmdouh.dev')
        <div style="background-color: #ffc107; color: #000; text-align: center; padding: 8px 0; font-weight: 600; font-size: 14px;">
            Development Environment || v{{ config('version.version', 'local') }} ({{ config('version.commit', 'dev') }})
        </div>
        <!-- Bug fix should be 1.2 -->
    @endif

    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    Mmdouh URL Shortener v{{ config('version.version', 'local') }}
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav mr-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Authentication Links -->
                        @guest
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                            </li>
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else

                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    @if(auth()->user()->is_admin)
                                        <span class="badge badge-secondary">{{ __('Admin') }}</span> &nbsp;
                                    @endif
                                    {{ auth()->user()->name }} <span class="caret"></span>
                                </a>

                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                    @if(auth()->user()->is_admin)
                                        <a href="/admin/links" class="dropdown-item">{{ __('All Links') }}</a>
                                        <a href="/admin/users" class="dropdown-item">{{ __('Users') }}</a>
                                        <div class="dropdown-divider"></div>
                                    @endif
                                    <a href="/dashboard" class="dropdown-item">{{ __('My Dashboard') }}</a>
                                    <a href="/settings" class="dropdown-item">{{ __('Settings') }}</a>

                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>

    <footer style="text-align: center; padding: 20px; color: #6c757d; font-size: 13px; border-top: 1px solid #e9ecef; margin-top: 40px;">
        @if(str_starts_with(request()->getHost(), 'az.') || str_starts_with(request()->getHost(), 'dev.'))
            Powered By <strong>Azure</strong>
        @else
            Powered By <strong>AWS</strong>
        @endif
    </footer>
</body>
</html>