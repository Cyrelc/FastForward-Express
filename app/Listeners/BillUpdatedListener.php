<?php

namespace App\Listeners;

use App\Events\BillUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BillUpdatedListener
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
     * @param  BillUpdated  $event
     * @return void
     */
    public function handle(BillUpdated $event)
    {
        $billRepo = new \App\Http\Repos\BillRepo();
        $billRepo->CheckRequiredFields($event->bill_id);
    }
}
