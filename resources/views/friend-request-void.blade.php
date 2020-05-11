@extends('layouts.nonauth')

@section('content')
  <h4>
    <i class="fa fa-times" style="color:red; margin-right:30px; font-size:30px;"></i>
    ERROR: This friend request has already been {{ $status }}.
  </h4>
@endsection