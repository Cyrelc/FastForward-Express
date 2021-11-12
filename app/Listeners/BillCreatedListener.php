<?php

namespace App\Listeners;

use App\Events\BillCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BillCreatedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  BillCreated  $event
     * @return void
     */
    public function handle(BillCreated $event)
    {
        activity('system_debug')->log('Bill created handler called for :  ' . $event->bill_id);
        $billRepo = new \App\Http\Repos\BillRepo();
        $billRepo->CheckRequiredFields($event->bill_id);
    }
}
