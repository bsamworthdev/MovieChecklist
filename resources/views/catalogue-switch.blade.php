@php($tvSelected = request()->routeIs('tv.*') || (!request()->routeIs('home', 'nonauthhome') && session('catalogue') === 'tv'))
<div class="btn-group ml-2 mr-3" role="group" aria-label="Choose catalogue">
    <a href="{{ request()->routeIs('admin') ? route('admin', ['catalogue' => 'movie']) : (Auth::check() ? route('home') : route('nonauthhome')) }}"
       class="btn btn-sm {{ $tvSelected ? 'btn-outline-primary' : 'btn-primary' }}"
       @if(!$tvSelected) aria-current="page" @endif>Movies</a>
    <a href="{{ request()->routeIs('admin') ? route('admin', ['catalogue' => 'tv']) : route('tv.index') }}"
       class="btn btn-sm {{ $tvSelected ? 'btn-primary' : 'btn-outline-primary' }}"
       @if($tvSelected) aria-current="page" @endif>TV Shows</a>
</div>
