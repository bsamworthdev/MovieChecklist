@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="container">
                        <div class="row">
                            <div class="col-12">
                             About
                            </div>
                          </div>
                      </div>
                  </div>
  
                  <div class="card-body">
                    <p>
                      <a href="https://www.themoviedb.org"><img src="{{ asset('images/tmdb-logo.svg') }}" alt="TMDB" width="120" height="16"></a>
                    </p>
                    <p>This product uses the TMDB API but is not endorsed or certified by TMDB.</p>
                    <p>UK streaming availability data is provided by <a href="https://www.justwatch.com/uk">JustWatch</a> via TMDB.</p>
                    <p>
                      <ul>
                        <li>Movie Checklist is a way to see which of IMDb's top 100 movies you have and haven't seen. </li>
                        <li>It also provides away to see which movies can be streamed online via Netflix, Amazon Prime, etc.</li>
                        <li>You can connect with friends and share lists together- Choosing a movie with mates has never been so easy!</li>
                      </ul>
                    </p>
                    <p>
                      <img src="/images/moviechecklist screenshot.png">
                    </p>
                    <p>
                      <div class="card bg-success" style="padding:10px;">
                        Movie Checklist is a non-profit personal project I created. Any data stored will not be passed on to third parties 
                        or used for spamming.
                      </div>
                    </p>
                    <p>
                      I welcome any constructive feedback, suggestions or ideas for features. Why not <a href="mailto:ben@moviechecklist&period;co&period;uk">drop me an email</a>.
                    </p>
                  </div>
                </div>
            </div>
        </div>
    </div>
@endsection
