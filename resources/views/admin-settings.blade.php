@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="container">
                        <div class="row">
                            <div class="col-6">
                                <div id="title">{{ $catalogue === 'tv' ? 'TV Shows' : 'Movies' }} Admin Settings</div>
                            </div>
                            @if($catalogue === 'movie')
                            <div class="col-6 d-flex flex-wrap justify-content-end">
                                <button type="button" id="update-movies-button" class="btn btn-primary mr-2 mb-2"><i class="fas fa-film mr-2" aria-hidden="true"></i>Fetch latest movies from IMDB</button>
                                <button type="button" id="generate-movie-thumbnails-button" class="btn btn-primary mb-2" data-url="{{ route('admin.movies.thumbnails') }}"><i class="fas fa-images mr-2" aria-hidden="true"></i>Generate missing thumbnails</button>
                            </div>
                            @else
                            <div class="col-6 d-flex flex-wrap justify-content-end"><button type="button" id="generate-tv-thumbnails-button" class="btn btn-primary mb-2" data-url="{{ route('admin.tv.thumbnails') }}"><i class="fas fa-images mr-2" aria-hidden="true"></i>Generate missing thumbnails</button></div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @include('admin-catalogues')
                    @if($catalogue === 'tv')
                        <p>{{ $tvCount }} TV shows in the database; {{ $tvRankedCount }} in the Top 250.</p>
                        @include('admin-tv-import')
                    @else
                    <users-table
                        :users = "{{ $users }}">
                    </users-table>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <button onclick="returnToTop()" id="topButton" title="Go to top">Back To Top</button>
</div>
@if($catalogue === 'movie')
@include('admin-movie-import')
@else
<div id="movie-import-notification" class="movie-import-notification alert" role="status" hidden><span id="movie-import-notification-text"></span></div>
@endif
@endsection

<script>

    // When the user scrolls down 20px from the top of the document, show the button
    window.onscroll = function() {
        var topButton = document.getElementById("topButton");
        if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
            topButton.style.display = "block";
        } else {
            topButton.style.display = "none";
        }
    }

    // When the user clicks on the button, scroll to the top of the document
    function returnToTop() {
        document.body.scrollTop = 0;
        document.documentElement.scrollTop = 0;
    }

</script>
<style>
    #title {
        font-size: 22px;
        font-weight: bold;
    }

    #topButton {
        display: none;
        position: fixed;
        bottom: 20px;
        right: 30px;
        z-index: 99;
        outline: none;
        background-color: black;
        color: white;
        cursor: pointer;
        padding: 10px;
        border-radius: 10px;
        font-size: 18px;
        opacity:0.7;
    }

    #topButton:hover {
        background-color: #555; /* Add a dark-grey background on hover */
    }
</style>
