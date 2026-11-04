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
                                        <div id="showFiltersButton"  class="row mt-1 mb-0">
                                            <div class="col-12">
                                                <button type="button" class="btn btn-light btn-block pt-0 pb-0 text-small" aria-controls="filtersContainer" aria-expanded="false" onclick="showFilters()">
                                                    <i class="fa fa-chevron-down"></i>
                                                    <small>More Filters</small>
                                                </button>
                                            </div>
                                        </div>
                                        <div id="filtersContainer" style="display:none;" class="row mt-3">
                                            <div class="col-lg-3 col-12">
                                                <div class="card" id="streamCard">
                                                    <div>
                                                        <span class="nowrap mr-2">
                                                            <label for="netflix_only_checkbox"> 
                                                                <img class="netflix_logo" title="Netflix" src="/images/netflix.jpg">
                                                            </label>
                                                            <input type="checkbox" 
                                                                class="form-input" 
                                                                id="netflix_only_checkbox" 
                                                                {{( $selectedNetflixOnly ? "checked" : '')}} 
                                                                onchange="return changeSelection();">
                                                        </span>
                                                        <span class="nowrap mr-2">
                                                            <label for="amazon_only_checkbox"> 
                                                                <img class="amazon_logo" title="Amazon Prime" src="/images/amazon.jpeg">
                                                            </label>
                                                            <input type="checkbox" 
                                                                class="form-input" 
                                                                id="amazon_only_checkbox" 
                                                                {{( $selectedAmazonOnly ? "checked" : '')}} 
                                                                onchange="return changeSelection();">
                                                        </span>
                                                        <span class="nowrap mr-2">
                                                            <label for="nowtv_only_checkbox"> 
                                                                <img class="nowtv_logo" title="Now TV" src="/images/nowtv.jpg">
                                                            </label>
                                                            <input type="checkbox" 
                                                                class="form-input" 
                                                                id="nowtv_only_checkbox" 
                                                                {{( $selectedNowtvOnly ? "checked" : '')}} 
                                                                onchange="return changeSelection();">
                                                        </span>
                                                        <span class="nowrap mr-2">
                                                            <label for="disney_plus_only_checkbox"> 
                                                                <img class="disney_plus_logo" title="Now TV" src="/images/disney_plus.jpg">
                                                            </label>
                                                            <input type="checkbox" 
                                                                class="form-input" 
                                                                id="disney_plus_only_checkbox" 
                                                                {{( $selectedDisneyPlusOnly ? "checked" : '')}} 
                                                                onchange="return changeSelection();">
                                                        </span>
                                                        <span class="nowrap mr-2">
                                                            <label for="hbo_max_only_checkbox">
                                                                <img class="hbo_max_logo" title="HBO Max" src="/images/hbo_max.png">
                                                            </label>
                                                            <input type="checkbox"
                                                                class="form-input"
                                                                id="hbo_max_only_checkbox"
                                                                {{( $selectedHboMaxOnly ? "checked" : '')}}
                                                                onchange="return changeSelection();">
                                                        </span>
<span class="nowrap mr-2">
                                                            <label for="apple_tv_only_checkbox">
                                                                <img class="apple_tv_logo" title="Apple TV" src="/images/apple_tv.png">
                                                            </label>
                                                            <input type="checkbox"
                                                                class="form-input"
                                                                id="apple_tv_only_checkbox"
                                                                {{( $selectedAppleTvOnly ? "checked" : '')}}
                                                                onchange="return changeSelection();">
                                                        </span>
<span class="nowrap mr-2">
                                                            <label for="paramount_plus_only_checkbox">
                                                                <img class="paramount_plus_logo" title="Paramount+" src="/images/paramount_plus.png">
                                                            </label>
                                                            <input type="checkbox"
                                                                class="form-input"
                                                                id="paramount_plus_only_checkbox"
                                                                {{( $selectedParamountPlusOnly ? "checked" : '')}}
                                                                onchange="return changeSelection();">
                                                        </span>
<span class="nowrap mr-2">
                                                            <label for="bbc_iplayer_only_checkbox">
                                                                <img class="bbc_iplayer_logo" title="BBC iPlayer" src="/images/bbc_iplayer.png">
                                                            </label>
                                                            <input type="checkbox"
                                                                class="form-input"
                                                                id="bbc_iplayer_only_checkbox"
                                                                {{( $selectedBbcIplayerOnly ? "checked" : '')}}
                                                                onchange="return changeSelection();">
                                                        </span>
<span class="nowrap mr-2">
                                                            <label for="itvx_only_checkbox">
                                                                <img class="itvx_logo" title="ITVX" src="/images/itvx.png">
                                                            </label>
                                                            <input type="checkbox"
                                                                class="form-input"
                                                                id="itvx_only_checkbox"
                                                                {{( $selectedItvxOnly ? "checked" : '')}}
                                                                onchange="return changeSelection();">
                                                        </span>
<span class="nowrap mr-2">
                                                            <label for="channel4_only_checkbox">
                                                                <img class="channel4_logo" title="Channel 4" src="/images/channel4.png">
                                                            </label>
                                                            <input type="checkbox"
                                                                class="form-input"
                                                                id="channel4_only_checkbox"
                                                                {{( $selectedChannel4Only ? "checked" : '')}}
                                                                onchange="return changeSelection();">
                                                        </span>
                                                    </div>
                                                    @include('streaming-select-all')
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
                                                        Popular Only <i data-toggle="tooltip" data-placement="right" title="" class="fa fa-info-circle" data-original-title="Popular movies are those with over 250k votes on imdb."></i>
                                                        <input type="checkbox" class="form-input" id="popular_only_checkbox" {{( $selectedPopularOnly ? "checked" : '')}} onchange="return changeSelection();">
                                                    </span>
                                                </label>
                                            </div>
                                            <div class="col-lg-3 col-12">
                                                <label class="vertAlign">
                                                    <span class="nowrap">
                                                        Unwatched By Me <input type="checkbox" class="form-input" id="unwatched_only_checkbox" {{( $selectedUnwatchedOnly ? "checked" : '')}} onchange="return changeSelection();">
                                                    </span>
                                                </label>
                                                @if(count($user->friends) > 0)
                                                <label class="vertAlign" >
                     
                                                        <span id="selectedFriendsLabel">Unwatched By Friends 
                                                            (<b>{{ $selectedUnwatchedByFriends == '' ? 0 : (substr_count($selectedUnwatchedByFriends, '|') + 1) }}</b>)
                                                        </span> &nbsp;    
                                                        <input id="selectedFriends" disabled name="selectedFriends" type="hidden" value="{{ $selectedUnwatchedByFriends }}">                                             
                                                        <a id="editFriendsLink" href="#" onclick="toggleFriendsContainer()">
                                                            edit
                                                        </a>
                                                </label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div id="hideFiltersButton" class="row mt-0 mb-0" style="display:none;">
                                        <div class="col-12">
                                            <button type="button" class="btn btn-light btn-block pt-0 pb-0 text-small" aria-controls="filtersContainer" aria-expanded="false" aria-label="Hide filters" onclick="hideFilters()">
                                                <i class="fa fa-chevron-up"></i>
                                            </button>
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
                    @include('streaming-attribution')
    
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

    <back-to-top></back-to-top>
</div>
@endsection

<script>


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
        var popularOnlyCheckbox = document.getElementById('popular_only_checkbox');
        var unwatchedOnlyCheckbox = document.getElementById('unwatched_only_checkbox');
        var selectedFriends = document.getElementById('selectedFriends');
        var friendMultiSelect = document.getElementById('friendMultiSelect');
        var favouritesOnlyCheckbox = document.getElementById('favourites_only_checkbox');
        var netflixOnlyCheckbox = document.getElementById('netflix_only_checkbox');
        var amazonOnlyCheckbox = document.getElementById('amazon_only_checkbox');
        var nowtvOnlyCheckbox = document.getElementById('nowtv_only_checkbox');
        var disneyPlusOnlyCheckbox = document.getElementById('disney_plus_only_checkbox');
        var hboMaxOnlyCheckbox = document.getElementById('hbo_max_only_checkbox');
        var appleTvOnlyCheckbox = document.getElementById('apple_tv_only_checkbox');
        var paramountPlusOnlyCheckbox = document.getElementById('paramount_plus_only_checkbox');
        var bbcIplayerOnlyCheckbox = document.getElementById('bbc_iplayer_only_checkbox');
        var itvxOnlyCheckbox = document.getElementById('itvx_only_checkbox');
        var channel4OnlyCheckbox = document.getElementById('channel4_only_checkbox');
        var timePeriodSelect = document.getElementById('time_period_select');
        var genreSelect = document.getElementById('genre_select');
        var searchInput = document.getElementById('search_input');
        
        var params = [];
        if (genreSelect.value != 'all') params.push('genre:' + genreSelect.value);
        if (timePeriodSelect.value != 'all') params.push('time:' + timePeriodSelect.value);
        if (englishOnlyCheckbox.checked) params.push('english:1'); 
        if (popularOnlyCheckbox.checked) params.push('popular:1'); 
        if (unwatchedOnlyCheckbox.checked) params.push('unwatched:1');
        if (favouritesOnlyCheckbox.checked) params.push('favourites:1');
        if (searchInput.value) params.push('search:' + searchInput.value);
        if (netflixOnlyCheckbox.checked) params.push('netflix:1');
        if (amazonOnlyCheckbox.checked) params.push('amazon:1');
        if (nowtvOnlyCheckbox.checked) params.push('nowtv:1');
        if (disneyPlusOnlyCheckbox.checked) params.push('disney_plus:1');
        if (hboMaxOnlyCheckbox.checked) params.push('hbo_max:1');
        if (appleTvOnlyCheckbox.checked) params.push('apple_tv:1');
        if (paramountPlusOnlyCheckbox.checked) params.push('paramount_plus:1');
        if (bbcIplayerOnlyCheckbox.checked) params.push('bbc_iplayer:1');
        if (itvxOnlyCheckbox.checked) params.push('itvx:1');
        if (channel4OnlyCheckbox.checked) params.push('channel4:1');
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


    window.onload = function(){
        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        })
    };

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

    .nowrap {
        white-space:nowrap;
    }
    .heart{
        color:red;
        font-size:17px;
    }
    .netflix_logo, .amazon_logo, .nowtv_logo, .disney_plus_logo, .hbo_max_logo, .apple_tv_logo, .paramount_plus_logo, .bbc_iplayer_logo, .itvx_logo, .channel4_logo{
        width:20px;
        height:20px;
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
        height:30px;
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

</style>
