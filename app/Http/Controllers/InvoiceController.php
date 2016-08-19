<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class InvoiceController extends Controller {
    public function __construct() {
        $this->middleware('auth');

        //API STUFF
        $this->sortBy = 'number';
        $this->maxCount = env('DEFAULT_INVOICE_COUNT', $this->maxCount);
        $this->itemAge = env('DEFAULT_INVOICE_AGE', '6 month');
        $this->class = new \App\Invoice;
    }

    public function index() {
        return view('invoices.invoices');
    }
}
