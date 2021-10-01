<?php

namespace App\Http\Controllers;

use App\Mail\ContactUs;

use App\Http\Validation\MailValidationRules;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Mail;

class GuestController extends Controller {
    public function about() {
        return view('guest.about');
    }

    public function contact() {
        return view('guest.contact');
    }

    public function home() {
        return view('guest.home');
    }

    public function requestDelivery() {
        return view('guest.requestDelivery');
    }

    public function requestQuote() {
        return view('guest.requestQuote');
    }

    public function services() {
        return view('guest.services');
    }

    public function submitContactForm(Request $req) {
        $mailValidation = new MailValidationRules();
        $temp = $mailValidation->GetContactUsValidationRules();

        $this->validate($req, $temp['rules'], $temp['messages']);

        Mail::to('fastfex@telus.net')->send(new ContactUs($req->email, $req->phone, $req->subject, $req->message));
    }
}

?>
