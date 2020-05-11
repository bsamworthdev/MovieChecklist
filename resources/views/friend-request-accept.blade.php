@extends('layouts.nonauth')

@section('content')
    <h4>
        <i class="fa fa-check" style="color:green; margin-right:30px; font-size:30px;"></i>
        You have accepted the friend request from {{ $user->name }}.
    </h4>
@endsection