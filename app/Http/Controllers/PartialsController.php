<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class PartialsController extends Controller
{
    public function GetContact(Request $req) {

        $contact = new \App\Contact();

        $contact->contact_id = $req->input('contact-id');
        $contact->first_name = $req->input('first-name');
        $contact->last_name = $req->input('last-name');

        $pn = new \App\PhoneNumber();
        $pn->phone_number_id = -2;
        $pn->phone_number = $req->input('phone-number');
        $pn->extension_number = $req->input('phone-number-ext');
        $contact->primaryPhone = $pn;

        $em = new \App\EmailAddress();
        $em->email_address_id = -2;
        $em->email = $req->input('email');
        $contact->primaryEmail =$em;

        $pn = new \App\PhoneNumber();
        $pn->phone_number_id = -2;
        $pn->phone_number = $req->input('secondary-phone-number');
        $pn->extension_number = $req->input('secondary-phone-number-ext');
        $contact->secondaryPhone = $pn;

        $em = new \App\EmailAddress();
        $em->email_address_id = -2;
        $em->email = $req->input('secondary-email');
        $contact->secondaryEmail = $em;

        $model = [
            'prefix' => 'contact-' . $req->input('contact-id'),
            'show_address' => false,
            'contact' => $contact
        ];

        return view('partials.contact', $model);
    }
}
