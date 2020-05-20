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
                                <div id="title">Admin Settings</div> 
                            </div>
                            <div class="col-6">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <users-table
                        :users = "{{ $users }}">
                    </users-table>
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
