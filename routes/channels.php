<?php

use App\Channel;
use App\User;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/
Broadcast::channel('presence', function ($user) {
  if (auth()->check()) {
    return $user->get($user::$profile)->where('id', $user->id)->first()->toArray();
  }
});

Broadcast::channel('users.{user}', function ($user, $user_request) {
    return $user->id == $user_request;
});

Broadcast::channel('channels.{channel}', function ($user, Channel $channel) {
    return $channel->isMember($user, $channel);
});

Broadcast::channel('voice.{channel}', function ($user, Channel $channel) {
  if (auth()->check() && $channel->isMember($user, $channel)) {
    return $user->get($user::$profile)->where('id', $user->id)->first()->toArray();
  }
});

/*Broadcast::channel('channels/{channel_id}', function ($user, $id) {
  return $user->id === Channel::findOrFail($id)->user_id;
});*/
