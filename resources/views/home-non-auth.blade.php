@extends('layouts.app-non-auth')

@section('content')
<div class="container">
    @foreach ($infoMessages as $infoMessage)
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="alert alert-{{ $infoMessage->style }}">
                    {!! $infoMessage->text !!}
                </div>
            </div>
        </div>
    @endforeach
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="container">
                        <div class="row">
                            <div class="col-12">
                                <form id="movie_form">
                                    <div class="container">
                                        <div class="row">
                                            <div class="col-lg-3 col-12">
                                                <div id="title">Movies</div> 
                                            </div>
                                            <div class="col-lg-3 col-12">
                                                <input id="search_input" type="text" class="form-control" placeholder="Search" 
                                                    onkeypress="return searchChanged(event);" value="{{ $selectedSearchText }}"/>
                                            </div>
                                            <div class="col-lg-3 col-12">
                                                <select id="time_period_select" class="form-control" onchange="return changeSelection();">
                                                    <option value="all" {{ ( $selectedTimePeriod =="all") ? 'selected' : '' }}> All Years </option>
                                                    @foreach ($timePeriods as $timePeriod)
                                                        <option value="{{ $timePeriod->time_period }}" {{ ( $timePeriod->time_period == $selectedTimePeriod) ? 'selected' : '' }}> 
                                                            {{ $timePeriod->label }} 
                                                        </option>
                                                    @endforeach    
                                                </select>
                                            </div>
                                            <div class="col-lg-3 col-12">
                                                <select id="genre_select" class="form-control" onchange="return changeSelection();">
                                                    <option value="all" {{ ( $selectedGenre =="all") ? 'selected' : '' }}> All Genres </option>
                                                    @foreach ($genres as $genre)    
                                                        <option value="{{ $genre->genre }}" {{ ( $genre->genre  == $selectedGenre) ? 'selected' : '' }}> 
                                                            {{ $genre->label }} 
                                                        </option>
                                                    @endforeach    
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-12">
                                                <div class="card" id="streamCard">
                                                    <label>
                                                        <span class="nowrap">
                                                            <label for="netflix_only_checkbox"> 
                                                                <img class="netflix_logo" title="Netflix" src="/images/netflix.jpg">
                                                            </label>
                                                            <input type="checkbox" 
                                                                class="form-input" 
                                                                id="netflix_only_checkbox" 
                                                                {{( $selectedNetflixOnly ? "checked" : '')}} 
                                                                onchange="return changeSelection();">
                                                        </span>
                                                        &nbsp;&nbsp;
                                                        <span class="nowrap">
                                                            <label for="amazon_only_checkbox"> 
                                                                <img class="amazon_logo" title="Amazon Prime" src="/images/amazon.jpeg">
                                                            </label>
                                                            <input type="checkbox" 
                                                                class="form-input" 
                                                                id="amazon_only_checkbox" 
                                                                {{( $selectedAmazonOnly ? "checked" : '')}} 
                                                                onchange="return changeSelection();">
                                                        </span>
                                                        &nbsp;&nbsp;
                                                        <span class="nowrap">
                                                            <label for="nowtv_only_checkbox"> 
                                                                <img class="nowtv_logo" title="Now TV" src="/images/nowtv.jpg">
                                                            </label>
                                                            <input type="checkbox" 
                                                                class="form-input" 
                                                                id="nowtv_only_checkbox" 
                                                                {{( $selectedNowtvOnly ? "checked" : '')}} 
                                                                onchange="return changeSelection();">
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-12"></div>
                                            <div class="col-lg-2 col-12">
                                                <label class="vertAlign">
                                                    <span class="nowrap">
                                                        <i class="fa fa-heart heart"></i> Favourites
                                                        <input type="checkbox" 
                                                            class="form-input" 
                                                            id="favourites_only_checkbox" 
                                                            {{( $selectedFavouritesOnly ? "checked" : '')}} 
                                                            onchange="return changeSelection();">
                                                    </span>
                                                </label>
                                            </div>
                                            <div class="col-lg-2 col-12">
                                                <label class="vertAlign">
                                                    <span class="nowrap">
                                                        English Only <input type="checkbox" class="form-input" id="english_only_checkbox" {{( $selectedEnglishOnly ? "checked" : '')}} onchange="return changeSelection();">
                                                    </span>
                                                </label>
                                            </div>
                                            <div class="col-lg-2 col-12">
                                                <label class="vertAlign">
                                                    <span class="nowrap">
                                                        Unwatched By Me <input type="checkbox" class="form-input" id="unwatched_only_checkbox" {{( $selectedUnwatchedOnly ? "checked" : '')}} onchange="return changeSelection();">
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

                    <scratch-card-grid-non-auth 
                        :watch_list="{{ $watchList }}" 
                        :movies="{{ $movies }}" >
                    </scratch-card-grid-non-auth>
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

    function searchChanged(e){
        if(e.charCode == 13){
            changeSelection();
            e.stopPropagation();
        }
    }

    function changeSelection() {
        var form = document.getElementById('movie_form');
        var englishOnlyCheckbox = document.getElementById('english_only_checkbox');
        var unwatchedOnlyCheckbox = document.getElementById('unwatched_only_checkbox');
        var favouritesOnlyCheckbox = document.getElementById('favourites_only_checkbox');
        var netflixOnlyCheckbox = document.getElementById('netflix_only_checkbox');
        var amazonOnlyCheckbox = document.getElementById('amazon_only_checkbox');
        var nowtvOnlyCheckbox = document.getElementById('nowtv_only_checkbox');
        var timePeriodSelect = document.getElementById('time_period_select');
        var genreSelect = document.getElementById('genre_select');
        var searchInput = document.getElementById('search_input');
        
        form.setAttribute('action', '/home_nonauth/' + genreSelect.value + 
            '/' + timePeriodSelect.value + 
            '/' + (englishOnlyCheckbox.checked ? '1' : '0') + 
            '/' + (unwatchedOnlyCheckbox.checked ? '1' : '0') + 
            '/' + (favouritesOnlyCheckbox.checked ? '1' : '0') + 
            '/' + (searchInput.value!=='' ? searchInput.value : 'null') + 
            '/' + (netflixOnlyCheckbox.checked ? '1' : '0') + 
            '/' + (amazonOnlyCheckbox.checked ? '1' : '0') + 
            '/' + (nowtvOnlyCheckbox.checked ? '1' : '0') + 
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
        width:100%;
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
    .netflix_logo, .amazon_logo, .nowtv_logo{
        width:30px;
        height:30px;
    }
    #streamCard{
        text-align:center;
        padding:5px;
        background-color:#d8d8d8;
        margin-top:4px;
    }
    #streamCard label{
        margin-bottom:0px;
    }
    .vertAlign { 
        height:40px;
        display: flex;
        align-items: center;
        margin-bottom:0px;
    }
</style>
