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
    //TODO validate that user has dispatch privileges
    if($user->cannot('createFull', Bill::class))
        abort(403);

    activity('system_debug')->log('Attempting to authenticate user: ' . $user->user_id . ' on dispatch channel');
    return true;
});

Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
