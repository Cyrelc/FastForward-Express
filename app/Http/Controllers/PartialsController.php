<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Models\Partials;
use App\Http\Repos;
use App\Http\Requests;

class PartialsController extends Controller
{
    public function NewContact(Request $req) {
        $contactModelFactory = new Partials\ContactModelFactory();
        $show_address = $req->show_address;
        $prefix = $req->prefix;
        $action = $req->action;
        $multi = $req->multi;
        $parent_prefix = $req->parent_prefix;
        $contact = $contactModelFactory->GetCreateModel($req);
        $contact->contact_prefix = $req->prefix;

        return view('partials.contact', compact('contact', 'prefix', 'action', 'show_address', 'parent_prefix', 'multi'));
    }

    public function NewPhone(Request $req) {
        $phoneModelFactory = new Partials\PhoneModelFactory();
        $selectionsRepo = new Repos\SelectionsRepo();

        $phone = $phoneModelFactory->GetCreateModel();
        $prefix = $req->phone_prefix;
        $types = $selectionsRepo->GetSelectionsByType('phone_type');

        return view('partials.phone_number', compact('phone', 'prefix', 'types'));
    }
}
