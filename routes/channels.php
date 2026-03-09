<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('orders.{id}', function ($user, $id) {
    $order = \App\Models\Order::find($id);
    return $order && ($user->id === $order->user_id || $user->id === $order->merchant->user_id || ($order->rider_id && $user->id === $order->rider->user_id));
});
