<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Movie Checklist</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #000000;
                background-image: url('/images/curtainBg.jpg');
                background-repeat:no-repeat;
                background-position:center;
                background-size:cover;
                color: #636b6f;
                font-family: 'Nunito', sans-serif;
                font-weight: 200;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
                background-color:white;
                padding:20px;
            }

            @media only screen and (orientation:portrait) and (max-width: 600px) {
                .title {
                    font-size: 40px;
                }
                .subtitle {
                    font-size: 24px;
                }
            }

            @media only screen and (orientation:landscape) and (max-width: 850px) {
                .title {
                    font-size: 40px;
                }
                .subtitle {
                    font-size: 24px;
                }
                .screenshot{
                    display:none;
                }
            }

            @media only screen and (orientation:portrait) and (min-width: 601px) {
                .title {
                    font-size: 84px;
                }

                .subtitle {
                    font-size: 34px;
                }
            }

            @media only screen and (orientation:landscape) and (min-width: 851px) {
                .title {
                    font-size: 84px;
                }

                .subtitle {
                    font-size: 34px;
                }
            }

            .links > a {
                color: #FFF;
                padding: 0 25px;
                font-size: 13px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 0px;
            }

            .screenshot{
                width:auto;
                height:25vh;
                min-height:180px;
                margin-bottom:20px;
            }

            .registerButton{
                font-size:30px;
            }
        </style>
    </head>
    <body>
        <div class="flex-center position-ref full-height">
            @if (Route::has('login'))
                <div class="top-right links">
                    @auth
                        <a href="{{ url('/home') }}">Home</a>
                    @else
                        <a href="{{ route('login') }}">Login</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}">Register</a>
                        @endif
                    @endauth
                    <a href="{{ url('/about') }}">About</a>
                </div>
            @endif

            <div class="content">
                <div class="title m-b-md">
                    Movie Checklist
                </div>
                <div class="subtitle m-b-md">
                    How many top 100 movies have you seen?
                </div>
                <img class="screenshot" src="images/moviechecklist screenshot.png" />
                <br>
                @guest
                    @if (Route::has('register'))
                        <button class="registerButton btn btn-success" onclick="location.href='{{ route('register') }}'">Sign Up (It's free!)</button>
                        <br>
                        <a href="{{ route('login') }}">I already have an account</a>
                        <br><br>
                        <button class="browseButton btn btn-info" onclick="location.href='/home_nonauth'">Browse Without Account</button>
                    @endif
                @endguest
               
            </div>
        </div>
    </body>
</html>
