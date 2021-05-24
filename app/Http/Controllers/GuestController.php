<?php

namespace App\Http\Controllers;

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
}

?>
