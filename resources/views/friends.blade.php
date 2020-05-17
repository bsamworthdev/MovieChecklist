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
                                <div id="title">My Friends</div> 
                            </div>
                            <div class="col-6">
                                <form id="friend_form">
                                    <select id="tag_select" class="form-control" onchange="return changeSelection();">
                                        <option value="all" {{ ( $selectedFriendTagNameId == "0") ? 'selected' : '' }}> All Friends </option>
                                        @foreach ($friendTagNames as $friendTagName)
                                            <option value="{{ $friendTagName->id }}" {{ ( $friendTagName->id == $selectedFriendTagNameId) ? 'selected' : '' }}> 
                                                {{ $friendTagName->name }} 
                                            </option>
                                        @endforeach    
                                    </select>
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
                    <friends-list
                        :friends="{{ $friends }}" 
                    >
                    </friends-list>     
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
        var form = document.getElementById('friend_form');
        var tagSelect = document.getElementById('tag_select');
        form.setAttribute('action', '/friends/' + tagSelect.value + 
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
