<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int)$user->id === (int)$id;
});

Broadcast::channel('support.ticket.{id}', function ($user, $id) {
    return \Illuminate\Support\Facades\Auth::check();
});

Broadcast::channel('admin.support.tickets', function ($user) {
    return \Illuminate\Support\Facades\Auth::check();
});
