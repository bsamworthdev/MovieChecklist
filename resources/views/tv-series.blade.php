@extends('layouts.app')

@section('content')
<div class="container">
    @if($ready)
        <tv-series-page :can-edit-streams="{{ auth()->check() && in_array(auth()->user()->role, ['admin', 'editor'], true) ? 'true' : 'false' }}"></tv-series-page>
    @else
        <div class="card"><div class="card-body"><h1 class="h3">TV Shows</h1><p class="mb-0">The TV Shows catalogue is being set up. Please check back shortly.</p></div></div>
    @endif
</div>
@endsection
