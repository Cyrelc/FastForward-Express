<?php

namespace App\Http\Controllers;

use App\Mail\ContactUs;
use App\Mail\OpenAccountRequest;
use App\Mail\RequestDelivery;

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

        Mail::to('contactus@fastforwardexpress.com')->send(new ContactUs($req->email, $req->phone, $req->subject, $req->message));
    }

    public function requestAccount(Request $req) {
        $mailValidation = new MailValidationRules();
        $temp = $mailValidation->GetRequestAccountValidationRules();

        $this->validate($req, $temp['rules'], $temp['messages']);

        Mail::to('contactus@fastforwardexpress.com')->send(new OpenAccountRequest($req));
    }

    public function requestDeliveryForm(Request $req) {
        $mailValidation = new MailValidationRules();
        $temp = $mailValidation->GetRequestDeliveryValidationRules();

        $this->validate($req, $temp['rules'], $temp['messages']);

        Mail::to('dispatch@fastforwardexpress.com')->send(new RequestDelivery($req));
    }
}

?>
