<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactUs extends Mailable
{
    use Queueable, SerializesModels;

    protected $email;
    protected $phone;
    protected $message;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email, $phone, $subject, $message)
    {
        $this->email = $email;
        $this->phone = $phone;
        $this->message = $message;

        $this->subject('FFE Contact Us - ' . $subject);
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.contactUs')->with([
            'email' => $this->email,
            'phone' => $this->phone,
            'subject' => $this->subject,
            'emailContents' => $this->message
        ]);
    }
}
