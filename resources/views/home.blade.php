@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-3 col-3">
                                <div id="title">My List</div> 
                            </div>
                            <div class="col-lg-9 col-9">
                                <form id="movie_form">
                                    <div class="container">
                                        <div class="row">
                                            <div class="col-lg-4 col-12">
                                                <label>
                                                    English 
                                                    <span class="nowrap">
                                                        Only <input type="checkbox" class="form-input" id="english_only_checkbox" {{( $selectedEnglishOnly ? "checked" : '')}} onchange="return changeSelection();">
                                                    </span>
                                                </label>
                                            </div>
                                            <div class="col-lg-4 col-12">
                                                <select id="time_period_select" class="form-select" onchange="return changeSelection();">
                                                    @foreach ($timePeriods as $key => $value)
                                                        <option value="{{ $key }}" {{ ( $key == $selectedTimePeriod) ? 'selected' : '' }}> 
                                                            {{ $value }} 
                                                        </option>
                                                    @endforeach    
                                                </select>
                                            </div>
                                            <div class="col-lg-4 col-12">
                                                <select id="genre_select" class="form-select" onchange="return changeSelection();">
                                                    @foreach ($genres as $key => $value)
                                                        <option value="{{ $key }}" {{ ( $key == $selectedGenre) ? 'selected' : '' }}> 
                                                            {{ $value }} 
                                                        </option>
                                                    @endforeach    
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-12"></div>
                                            <div class="col-lg-3 col-12">
                                                <label>
                                                    <i class="fa fa-heart heart"></i>
                                                    <span class="nowrap">
                                                        Favourites
                                                        <input type="checkbox" 
                                                            class="form-input" 
                                                            id="favourites_only_checkbox" 
                                                            {{( $selectedFavouritesOnly ? "checked" : '')}} 
                                                            onchange="return changeSelection();">
                                                    </span>
                                                </label>
                                            </div>
                                            <div class="col-lg-3 col-12">
                                                <label>
                                                    <img class="netflix_logo" src="/images/netflix.jpg">
                                                    <span class="nowrap">
                                                        Netflix
                                                        <input type="checkbox" 
                                                            class="form-input" 
                                                            id="netflix_only_checkbox" 
                                                            {{( $selectedNetflixOnly ? "checked" : '')}} 
                                                            onchange="return changeSelection();">
                                                    </span>
                                                </label>
                                            </div>
                                            <div class="col-lg-3 col-12">
                                                <label>
                                                    <img class="amazon_logo" src="/images/amazon.jpeg">
                                                    <span class="nowrap">
                                                        Amazon
                                                        <input type="checkbox" 
                                                            class="form-input" 
                                                            id="amazon_only_checkbox" 
                                                            {{( $selectedAmazonOnly ? "checked" : '')}} 
                                                            onchange="return changeSelection();">
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <scratch-card-grid 
                        :user="{{ $user }}" 
                        :movies="{{ $movies }}" >
                    </scratch-card-grid>
                </div>
            </div>
        </div>
    </div>
    <button onclick="returnToTop()" id="topButton" title="Go to top">Back To Top</button>
</div>
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

    function changeSelection() {
        var form = document.getElementById('movie_form');
        var englishOnlyCheckbox = document.getElementById('english_only_checkbox');
        var favouritesOnlyCheckbox = document.getElementById('favourites_only_checkbox');
        var netflixOnlyCheckbox = document.getElementById('netflix_only_checkbox');
        var amazonOnlyCheckbox = document.getElementById('amazon_only_checkbox');
        var timePeriodSelect = document.getElementById('time_period_select');
        var genreSelect = document.getElementById('genre_select');
        form.setAttribute('action', '/home/' + genreSelect.value + 
            '/' + timePeriodSelect.value + 
            '/' + (englishOnlyCheckbox.checked ? '1' : '0') + 
            '/' + (favouritesOnlyCheckbox.checked ? '1' : '0') + 
            '/' + (netflixOnlyCheckbox.checked ? '1' : '0') + 
            '/' + (amazonOnlyCheckbox.checked ? '1' : '0') + 
            '/');
        form.submit();
    }

    // When the user clicks on the button, scroll to the top of the document
    function returnToTop() {
        document.body.scrollTop = 0;
        document.documentElement.scrollTop = 0;
    }

</script>
<style>
    #movie_form{
        margin-bottom:0px;
        text-align:left;
    }
    #movie_form select{
        min-width:95%;
    }
    #title{
        font-size:22px;
        font-weight:bold;
    }
    .card-header{
        font-size:16px;
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
    .nowrap {
        white-space:nowrap;
    }
    .heart{
        color:red;
        font-size:17px;
    }
    .netflix_logo, .amazon_logo{
        width:20px;
        height:20px;
    }
</style>
