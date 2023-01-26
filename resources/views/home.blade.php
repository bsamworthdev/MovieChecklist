@extends('layouts.app')

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
                <div class="card-header pb-0 pl-0 pr-0">
                    <div class="container">
                        <div class="row">
                            <div class="col-12">
                                <form id="movie_form" name="movie_form" action="/home">
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
                                        <div id="showFiltersButton" style="{{ ($user->filters_shown == 1) ? 'display:none;' : '' }}" class="row mt-1 mb-0">
                                            <div class="col-12">
                                                <div class="button btn btn-light btn-block pt-0 pb-0 text-small" onclick="showFilters()">
                                                    <i class="fa fa-chevron-down"></i>
                                                    <small>More Filters</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="filtersContainer" style="{{ ($user->filters_shown == 0) ? 'display:none;' : '' }}" class="row mt-3">
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
                                            <div class="col-lg-3 col-12">
                                                <label class="vertAlign" >
                                                    @if(count($user->friends) > 0)
                     
                                                        <span id="selectedFriendsLabel">Unwatched By Friends 
                                                            (<b>{{ $selectedUnwatchedByFriends == '' ? 0 : (substr_count($selectedUnwatchedByFriends, '|') + 1) }}</b>)
                                                        </span> &nbsp;    
                                                        <input id="selectedFriends" disabled name="selectedFriends" type="hidden" value="{{ $selectedUnwatchedByFriends }}">                                             
                                                        <a id="editFriendsLink" href="#" onclick="toggleFriendsContainer()">
                                                            edit
                                                        </a>
                                                    @else 
                                                        <span id="inactiveSelectedFriendsLabel" title="Add friends to use this feature">Unwatched By Friends (0)</span> &nbsp;    
                                                    @endif
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="hideFiltersButton" class="row mt-0 mb-0" style="{{ ($user->filters_shown == 0) ? 'display:none;' : '' }}">
                                        <div class="col-12">
                                            <div class="button btn btn-light btn-block pt-0 pb-0 text-small" onclick="hideFilters()">
                                                <i class="fa fa-chevron-up"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" class="form-input"  id="filters" name="filters">
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
                        :watch_list="{{ $watchList }}" 
                        :movies="{{ $movies }}" 
                        :filters="{{ $filters }}">
                    </scratch-card-grid>
                </div>


            </div>
        </div>
    </div>  

    <div id="friendsContainer">
        <div class="container">
            <div class="row friendsHeader">
                <div class="col-7 friendsHeaderTitle">
                    <b>Select Friends</b>
                </div>
                <div class="col-3 friendsHeaderButtons">
                    <a href='#' class="selectAll" onclick="selectAllFriends()">all</a>
                    |
                    <a href='#' class="clearAll" onclick="clearAllFriends()">clear</a>
                </div>
                <div class="col-2 friendsHeaderClose">
                    <i class="fa fa-times" onclick="hideFriendsContainer()"></i>
                </div>
            </div>
            <div class="friendsList">
                @foreach($user->friends as $key=>$friend)
                    <div class="row friend" friend_id="{{ $friend->id }}">
                        <div class="col-8">
                            <label for="friendCheckbox{{ $key }}">{{ $friend->name ? : $friend->username }}</label>
                        </div>
                        <div class="col-4">
                            <input id="friendCheckbox{{ $key }}" type="checkbox" class="friendCheckbox" {{ in_array($friend->id, explode('|', $selectedUnwatchedByFriends)) ? 'checked' : '' }}>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="row buttonRow">
                <div class="col-6">
                    <button class="btn btn-info" onclick="hideFriendsContainer()">Close</button>
                </div>
                <div class="col-6">
                    <button class="btn btn-success"  onclick="saveFriendSelection()">Apply</button>
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

    function showFilters(){
        var filtersContainer = document.getElementById("filtersContainer");
        var showFiltersButton = document.getElementById("showFiltersButton");
        var hideFiltersButton = document.getElementById("hideFiltersButton");

        axios.post('/updateFiltersShown/1')
        .then((response) => {
            filtersContainer.style.display = "";
            showFiltersButton.style.display = "none";
            hideFiltersButton.style.display = "";
        })
        .catch((error) => {
            console.log(error);
        });
        
    }

    function hideFilters(){
        var filtersContainer = document.getElementById("filtersContainer");
        var showFiltersButton = document.getElementById("showFiltersButton");
        var hideFiltersButton = document.getElementById("hideFiltersButton");


        axios.post('/updateFiltersShown/0')
        .then((response) => {
            filtersContainer.style.display = "none";
            showFiltersButton.style.display = "";
            hideFiltersButton.style.display = "none";
        })
        .catch((error) => {
            console.log(error);
        });
    }

    function showFriendsContainer(){
        // var editFriendsLink = document.getElementById('editFriendsLink');
        // var friendsContainer = document.getElementById('friendsContainer');
        // friendsContainer.style.left = editFriendsLink.offsetLeft;
        // friendsContainer.style.top = (editFriendsLink.offsetTop+30);
        // friendsContainer.style.display="block";

        var editFriendsLink = document.querySelector("#editFriendsLink").getBoundingClientRect();
        var offset = { 
                top: editFriendsLink.top + window.scrollY, 
                left: editFriendsLink.left + window.scrollX, 
            };
        var friendsContainer = document.getElementById('friendsContainer');
        friendsContainer.style.left = offset.left - 230;
        friendsContainer.style.top = offset.top + 30;
        friendsContainer.style.display="block";
    }
    function hideFriendsContainer(){
        document.getElementById('friendsContainer').style.display="none";
    }

    function toggleFriendsContainer(){
        if (document.getElementById('friendsContainer').style.display == 'block'){
            hideFriendsContainer();
        } else {
            showFriendsContainer();
        }
    }

    function searchChanged(e){
        if(e.charCode == 13){
            changeSelection();
            e.stopPropagation();
        }
    }

    function friendMultiSelectChanged(){
        changeSelection();

        var friendMultiSelect = document.getElementById('friendMultiSelect');
        
    }
    
    function changeSelection() {
        var form = document.getElementById('movie_form');
        var filters = document.getElementById('filters');
        var englishOnlyCheckbox = document.getElementById('english_only_checkbox');
        var unwatchedOnlyCheckbox = document.getElementById('unwatched_only_checkbox');
        var selectedFriends = document.getElementById('selectedFriends');
        var friendMultiSelect = document.getElementById('friendMultiSelect');
        var favouritesOnlyCheckbox = document.getElementById('favourites_only_checkbox');
        var netflixOnlyCheckbox = document.getElementById('netflix_only_checkbox');
        var amazonOnlyCheckbox = document.getElementById('amazon_only_checkbox');
        var nowtvOnlyCheckbox = document.getElementById('nowtv_only_checkbox');
        var timePeriodSelect = document.getElementById('time_period_select');
        var genreSelect = document.getElementById('genre_select');
        var searchInput = document.getElementById('search_input');
        
        var params = [];
        if (genreSelect.value != 'all') params.push('genre:' + genreSelect.value);
        if (timePeriodSelect.value != 'all') params.push('time:' + timePeriodSelect.value);
        if (englishOnlyCheckbox.checked) params.push('english:1'); 
        if (unwatchedOnlyCheckbox.checked) params.push('unwatched:1');
        if (favouritesOnlyCheckbox.checked) params.push('favourites:1');
        if (searchInput.value) params.push('search:' + searchInput.value);
        if (netflixOnlyCheckbox.checked) params.push('netflix:1');
        if (amazonOnlyCheckbox.checked) params.push('amazon:1');
        if (nowtvOnlyCheckbox.checked) params.push('nowtv:1');
        if (selectedFriends && selectedFriends.value) params.push('friends:' + selectedFriends.value);

        if (params.length > 0){
            // window.location.href="/home?" + params.join('&');
            filters.setAttribute('value', params.join(';'));
        } else {
            window.location.href="/home";
            return;
        }
        form.submit();
    }

    function saveFriendSelection(){
        var friendsSelected = [];
        friends = document.getElementsByClassName("friend");
        for(var i = 0; i < friends.length; i ++){
            if (friends[i].getElementsByClassName("friendCheckbox")[0].checked) {
                friendsSelected.push(friends[i].getAttribute('friend_id'));
            }
        }
        document.getElementById('selectedFriends').value = friendsSelected.join('|');

        event.stopPropagation();
        hideFriendsContainer();
        changeSelection();
    }

    function selectAllFriends(){
        friends = document.getElementsByClassName("friend");
        for(var i = 0; i < friends.length; i ++){
            friends[i].getElementsByClassName("friendCheckbox")[0].checked = true;
        }
    }
    
    function clearAllFriends(){
        friends = document.getElementsByClassName("friend");
        for(var i = 0; i < friends.length; i ++){
            friends[i].getElementsByClassName("friendCheckbox")[0].checked = false;
        }
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


    #friendsContainer {
        position:absolute;
        right:0;
        top:0;
        background-color:white;
        width:80%;
        max-width:270px;
        display:none;
        border:1px solid #C0C0C0;
        z-index:99;
    }

    #friendsContainer > .container{
        padding-left:0px;
        padding-right:0px;
    }

    #friendsContainer .friendsList{
        max-height:40vh;
        min-height:80px;
        overflow:auto;
    }

    #friendsContainer .friendsList {
        margin-top:10px;
        margin-bottom:10px;
    }

    #friendsContainer .friend{
        margin-left:0px;
        margin-right: 0px;
    }

    #friendsContainer .buttonRow{
        padding:10px;
        border-top:1px solid #C0C0C0
    }

    #friendsContainer .buttonRow div{
        text-align: center;
    }

    #friendsContainer .friendsHeader {
        margin-left: 0px;
        margin-right: 0px;
        border-bottom: 1px solid #C0C0C0;
    }

    #friendsContainer .friendsHeaderTitle {
        font-size: 18px;
    }

    #friendsContainer .friendsHeaderClose {
        text-align: right;
    }

    #friendsContainer .friendsHeaderClose .fa {
        padding-top:7px;
        cursor:pointer;
    }

    #friendsContainer .friendsHeaderButtons{
        white-space:nowrap;
    }

    #inactiveSelectedFriendsLabel{
        opacity:0.4;
    }
</style>
