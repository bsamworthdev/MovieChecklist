@extends('layouts.nonauth')

@section('content')
  <h4>
    About
  </h4>
  <p>
    <ul>
      <li>Movie Checklist is a way to see which of IMDb's top 100 movies you have and haven't seen. </li>
      <li>It also provides away to see which movies can be streamed online via Netflix, Amazon Prime, etc.</li>
      <li>You can connect with friends and share lists together- Choosing a movie with mates has never been so easy!</li>
    </ul>
  </p>
  <p>
    <img src="/images/moviechecklist screenshot.png">
  </p>
  <p>
    <div class="card bg-success">
      Movie Checklist is a non-profit personal project I created. Any data stored will not be passed on to third parties 
      or used for spamming.
    </div>
  </p>
  <p>
    I welcome any constructive feedback, suggestions or ideas for features. Why not <a href="mailto:ben@moviechecklist&period;co&period;uk">drop me an email</a>.
  </p>
@endsection