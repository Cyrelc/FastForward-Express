<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

use App\Mail\InvoiceFinalized;

class SendInvoiceFinalizedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $recipients = [];
    protected $invoiceId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($billingUsers, $invoice)
    {
        activity('jobs')->log('Constructing Invoice Finalized Job for Invoice #' . $invoice->invoice_id);
        $this->invoiceId = $invoice->invoice_id;
        foreach($billingUsers as $billingUser)
            $this->recipients[] = $billingUser->email;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $invoiceRepo = new \App\Http\Repos\InvoiceRepo();
        $invoice = $invoiceRepo->GetById($this->invoiceId);
        activity('jobs')->log('Handling Invoice Finalized Job for Invoice #' . $invoice->invoice_id);

        if($invoice->finalized && !$invoice->notification_sent) {
            Mail::to($this->recipients)->send(new InvoiceFinalized($invoice));
            $invoiceRepo->MarkNotificationSent($invoice->invoice_id);
        } else
            return;
    }
}
