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
        if($this->isHoneypotFilled($req))
            return response()->json(['success' => true]);

        $mailValidation = new MailValidationRules();
        $temp = $mailValidation->GetContactUsValidationRules();

        $this->validate($req, $temp['rules'], $temp['messages']);

        Mail::to('contactus@fastforwardexpress.ca')->send(new ContactUs($req->email, $req->phone, $req->subject, $req->message));
        return response()->json([
            'success' => true
        ]);
    }

    public function requestAccount(Request $req) {
        if($this->isHoneypotFilled($req))
            return response()->json(['success' => true]);

        $mailValidation = new MailValidationRules();
        $temp = $mailValidation->GetRequestAccountValidationRules();

        $this->validate($req, $temp['rules'], $temp['messages']);

        Mail::to('contactus@fastforwardexpress.ca')->send(new OpenAccountRequest($req));
        return response()->json([
            'success' => true
        ]);
    }

    public function requestDeliveryForm(Request $req) {
        if($this->isHoneypotFilled($req))
            return response()->json(['success' => true]);

        $mailValidation = new MailValidationRules();
        $temp = $mailValidation->GetRequestDeliveryValidationRules();

        $this->validate($req, $temp['rules'], $temp['messages']);

        Mail::to('dispatch@fastforwardexpress.ca')->send(new RequestDelivery($req));
        return response()->json([
            'success' => true
        ]);
    }

    /**
     * The public forms include a hidden "website" field that people never see. Bots that fill in
     * every input get a fake success response so they don't retry, and no email is sent.
     */
    private function isHoneypotFilled(Request $req) {
        return filled($req->input('website'));
    }
}

?>
