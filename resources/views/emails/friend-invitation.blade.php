<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Invitation</title>
</head>
<body>
  <h4>You have been invited by {{ $user->name }} to create an account with Movie Checklist.</h4>

  <a href="{{ URL::to('/') }}/register">Click here to register your account</a>
  
</body>
</html>