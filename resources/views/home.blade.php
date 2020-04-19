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
                                <option value="all">All Movies</option>
                                <option value="animated">Animated Movies</option>
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

                    <scratch-card-grid :user="{{ $user }}" :movies="{{ $movies }}"></scratch-card-grid>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
<script>
    function changeGenre() {
        var form = document.getElementById('movie_form');
        var select = document.getElementById('movie_select');
        form.setAttribute('action', '/home/' + select.value + '/');
        form.submit();
    }
</script>
<style>
    #movie_form{
        float:right;
        margin-bottom:0px;
    }
</style>
