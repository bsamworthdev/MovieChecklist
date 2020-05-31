<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Movie Checklist</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <style>
        .nav-link.disabled
        {
            color:#D2D2D2;
        }
        .trophyContainer.disabled{
            opacity:0.4;
        }
    </style>
    
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    Movie Checklist
                </a>
                <div data-v-9e0f86b4="" class="container" title="Create an account to use this feature">
                    <div data-v-9e0f86b4="" class="trophyContainer disabled">
                        <i data-v-9e0f86b4="" class="fas fa-trophy trophy gold"></i>0
                    </div> 
                    <div data-v-9e0f86b4="" class="trophyContainer disabled">
                        <i data-v-9e0f86b4="" class="fas fa-trophy trophy silver"></i>0
                    </div> 
                    <div data-v-9e0f86b4="" class="trophyContainer disabled">
                        <i data-v-9e0f86b4="" class="fas fa-trophy trophy bronze"></i>0
                    </div>
                </div>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/home">Home</a>
                        </li>
                        <li class="nav-item" title="Create an account to use this feature">
                            <a class="nav-link disabled">Friends</a>
                        </li>
                    </ul>
                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        

        <main class="py-4">
            @yield('content')
        </main>
    </div>
</body>
</html>
