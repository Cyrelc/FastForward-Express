<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BillCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $bill_id;
    public $time_pickup_scheduled;
    public $time_delivery_scheduled;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($bill)
    {
        activity('system_debug')->log('Bill created: ' . $bill->bill_id);
        $this->bill_id = $bill->bill_id;
        $this->time_pickup_scheduled = $bill->time_pickup_scheduled;
        $this->time_delivery_scheduled = $bill->time_delivery_scheduled;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('dispatch');
    }
}
