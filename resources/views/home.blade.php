@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>
                        My List 
                        <form id="movie_form">
                            <select id="movie_select" class="form-select" onchange="return changeGenre();">
                                @foreach ($genres as $key => $value)
                                    <option value="{{ $key }}" {{ ( $key == $selectedGenre) ? 'selected' : '' }}> 
                                        {{ $value }} 
                                    </option>
                                @endforeach    
                            </select>
                        </form>
                    </h4>
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

    function changeGenre() {
        var form = document.getElementById('movie_form');
        var select = document.getElementById('movie_select');
        form.setAttribute('action', '/home/' + select.value + '/');
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
        float:right;
        margin-bottom:0px;
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
