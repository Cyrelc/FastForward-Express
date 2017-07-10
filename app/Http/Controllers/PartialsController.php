<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class PartialsController extends Controller
{
    public function GetContact(Request $req) {
        $collector = new \App\Http\Collectors\ContactCollector();

        $contact = $collector->ToObject(
        $collector->Collect($req, 'new'),
        $collector->CollectPhoneNumber($req, 0, true),
        $collector->CollectEmail($req, 0, true),
        $collector->CollectPhoneNumber($req, 0, false),
        $collector->CollectEmail($req, 0, false));

        $contact->contact_id = $req->input('new-contact-id');

        $showAddress = $req->input('include-address') !== null && $req->input('include-address') == 'true';
        $model = [
            'prefix' => 'contact-' . $req->input('new-contact-id'),
            'show_address' => $showAddress,
            'contact' => $contact,
            'multi' => 'true',
            'multi_div_prefix' => $req->input('multi-contact-prefix')
        ];

        $model['contact']->is_new = 'true';

        if($showAddress) {
            $addrCollector = new \App\Http\Collectors\AddressCollector();
            $model['contact']->address = $addrCollector->ToObject($addrCollector->Collect($req, 'new', true));
        }

        return view('partials.contact', $model);
    }
}
