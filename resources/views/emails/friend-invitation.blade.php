<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Invitation</title>
</head>
<body>
  <p>Hi!</p>
  <h4>You have been invited by {{ $user->name }} to create an account with Movie Checklist.</h4>
  <p>
    <img src="https://moviechecklist.co.uk/images/moviechecklist screenshot.png">
  </p>
  <p>
    <ul>
      <li> Movie Checklist is a fun and easy way to see which of IMDb's top 100 movies you have and haven't seen.</li>
      <li> You can also see which movies can be streamed online via Netflix, Amazon Prime, etc.</li>
      <li> You can connect with friends and share lists together- Choosing a movie with mates has never been so easy!</li>
      <li><a href="{{ URL::to('/') }}/about">Find out more...</a></li>
    </ul>
  </p>
  <p>
    <a href="{{ URL::to('/') }}/register">Register your account</a>
  </p>
</body>
</html>