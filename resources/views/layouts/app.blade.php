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
        .trophy.gold{ color:gold; }
        .trophy.silver{ color:silver; }
        .trophy.bronze{ color:#cd7f32; }
        .fa-trophy {margin-right:2px!important;}
    </style>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script>
        $(document).ready(function(){
            $('body :not(div[data-toggle="tooltip"])').on('click',function(){
                $('[data-toggle="tooltip"]').tooltip("hide");
            });
            
            $('div[data-toggle="tooltip"]')
            .tooltip({
                title: "No trophies yet", 
                trigger: "click", 
                html: true
            })
            .click(function(e){
                e.stopPropagation();
            }); 
            })
    </script>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    Movie Checklist
                </a>
                @guest
                @else
                @php
                $trophyByColor = [];
                @endphp
                @foreach (Auth::user()->trophies as $trophy)
                    @php
                    $trophyByColor[$trophy->color][] = $trophy;
                    if (!isset($trophyByColor[$trophy->color]['details'])){
                        $trophyByColor[$trophy->color]['details'] = '';
                    }
                    $trophyByColor[$trophy->color]['details'] .= $trophy->details.'<br/>';
                    @endphp
                @endforeach
                
                <div data-toggle="tooltip" title="{{ isset($trophyByColor['gold']['details']) ? $trophyByColor['gold']['details'] : '' }}">
                    <i class="fas fa-trophy trophy gold"></i>{{ isset($trophyByColor['gold']) ? count($trophyByColor['gold'])-1 : 0 }}
                    &nbsp;
                </div>
                <div data-toggle="tooltip" title="{{ isset($trophyByColor['silver']['details']) ? $trophyByColor['silver']['details'] : '' }}">
                    <i class="fas fa-trophy trophy silver"></i>{{ isset($trophyByColor['silver']) ? count($trophyByColor['silver'])-1 : 0 }}
                    &nbsp;
                </div>
                <div data-toggle="tooltip" title="{{ isset($trophyByColor['bronze']['details']) ? $trophyByColor['bronze']['details'] : '' }}">
                    <i class="fas fa-trophy trophy bronze"></i>{{ isset($trophyByColor['bronze']) ? count($trophyByColor['bronze'])-1 : 0 }}
                    &nbsp;
                </div>
                @endguest
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav mr-auto">
                        @guest
                        @else
                        <li class="nav-item">
                            <a class="nav-link" href="/home">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/friends">Friends</a>
                        </li>
                        @if (Auth::user()->role == 'admin')
                            <li class="nav-item">
                                <a class="nav-link" href="/admin">Admin</a>
                            </li>
                        @endif
                        @endguest
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
                                    {{ Auth::user()->name?:Auth::user()->username }} <span class="caret"></span>
                                </a>

                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
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
</body>
</html>
