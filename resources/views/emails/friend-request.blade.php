<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Document</title>
</head>
<body>
  <h4>{{ $user->name }} sent you a friend request</h4>

  <a href="{{ URL::to('/') }}/acceptFriendRequest/{{ $token }}">Click here to accept</a>
  
</body>
</html>