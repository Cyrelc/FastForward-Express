<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OpenAccountRequest extends Mailable
{
    use Queueable, SerializesModels;

    protected $email;
    protected $phone;
    protected $message;
    protected $estimatedDeliveryCount;
    protected $companyName;
    protected $contactName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($req)
    {
        $this->email = $req->email;
        $this->phone = $req->phone;
        $this->message = $req->message;
        $this->estimatedDeliveryCount = $req->deliveryCount;
        $this->companyName = $req->companyName;
        $this->contactName = $req->contactName;

        $this->subject('Open Account Request - ' . $this->companyName);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.openAccountRequest')->with([
            'email' => $this->email,
            'phone' => $this->phone,
            'contactName' => $this->contactName,
            'companyName' => $this->companyName,
            'emailContents' => $this->message,
            'estimatedDeliveryCount' => $this->estimatedDeliveryCount
        ]);
    }
}
