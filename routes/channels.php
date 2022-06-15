<?php

use Illuminate\Support\Facades\Broadcast;

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

Broadcast::channel('dispatch', function ($user) {
    activity('system_debug')->log('Attempting to authenticate user: ' . $user->user_id . ' on dispatch channel ' . $user->cannot('viewDispatch', Bill::class));
    if($user->cannot('bills.edit.dispatch.*'))
        abort(403);

    return true;
});

Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
