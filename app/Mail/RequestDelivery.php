<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestDelivery extends Mailable
{
    use Queueable, SerializesModels;

    protected $email;
    protected $contactName;
    protected $message;
    protected $phone;
    protected $pickupAddress;
    protected $pickupPostal;
    protected $pickupTime;
    protected $deliveryAddres;
    protected $deliveryTime;
    protected $weightKg;
    protected $dimensions;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($req) {
        $this->email = $req->email;
        $this->message = $req->description;
        $this->contactName = $req->input('contact-name');
        $this->phone = $req->phone;
        $this->pickupAddress = $req->input('pickup-address');
        $this->pickupPostal = $req->input('pickup-postal-code');
        $this->pickupTime = $req->input('pickup-time');
        $this->deliveryAddress = $req->input('delivery-address');
        $this->deliveryPostal = $req->input('delivery-postal-code');
        $this->deliveryTime = $req->input('delivery-time');
        $this->weightKg = $req->input('weight-kg');
        $this->dimensions = $req->dimensions;

        $this->subject('Request Delivery - ' . $req->input('pickup-time') . ' - ' . $req->input('contact-name'));
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.requestDelivery')->with([
            'email' => $this->email,
            'phone' => $this->phone,
            'contactName' => $this->contactName,
            'emailContents' => $this->message,
            'pickupAddress' => $this->pickupAddress,
            'pickupPostal' => $this->pickupPostal,
            'pickupTime' => $this->pickupTime,
            'deliveryAddress' => $this->deliveryAddress,
            'deliveryPostal' => $this->deliveryPostal,
            'deliveryTime' => $this->deliveryTime,
            'weightKg' => $this->weightKg,
            'dimensions' => $this->dimensions
        ]);
    }
}
