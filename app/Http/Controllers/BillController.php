<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Validator;
use Redirect;

use App\Http\Requests;
use App\Bill;
use App\ReferenceType;

class BillController extends Controller {
    public function __construct() {
        $this->middleware('auth');
    }

    protected function validator(array $data) {
        return Validator::make($data, [
            'number'        => 'required|integer', //|unique:bills
            'date'          => 'required|date',
            'description'   => 'max:255',
            'ref_id'        => 'sometimes|exists:ref_type,id',
            'manifest'      => 'date',
            'payment_id'    => 'required|exists:payment_type,id',
            'amount'        => 'required|numeric',
            'int_amount'    => 'required|numeric',
            'driver_amount' => 'required|numeric'
        ]);
    }

    public function index() {
        return view('bills.bills');
    }

    public function store(Request $req) {
        //Make sure the user has access to edit both: orig_bill and number (both are bill numbers, orig_bill will be the one to modify or -1 to create new)


        $values = $req->all();
        //Nicely handle the error below / Insert a new reference into the table?
        if ($values['reference'] != '')
            //Be able to create new reference types here?
            $values['ref_id'] = ReferenceType::where('name', '=', $values['reference'])->firstOrFail()->id;

        $val = $this->validator($values);

        if ($val->fails())
            return json_encode(array(
                    'success' => false,
                    'errors' => $val->errors()->all()
            ));

        if ($values['orig_bill'] == -1 || $values['orig_bill'] != $values['number']) {
            // Create new bill / Change bill number
            if (!$values['force'] && Bill::where('number', '=', $values['number'])->first()) {
                //Bill exists already

                return json_encode(array(
                        'success' => false,
                        'errors' => array('Bill number ' . $values['number'] . ' already exists, submit again to overwrite.'),
                        'setforced' => true
                ));
            } else if ($values['force']) {
                $bill = Bill::where('number', '=', $values['number'])->first();
                if ($bill)
                    $bill->delete();
            }
            Bill::create(array(
                'number'        => $values['number'],
                'date'          => $values['date'],
                'description'   => $values['description'],
                'ref_id'        => isset($values['ref_id']) ? $values['ref_id'] : null,
                'payment_id'    => $values['payment_id'],
                'amount'        => $values['amount'],
                'int_amount'    => $values['int_amount'],
                'driver_amount' => $values['driver_amount'],
                'taxes'         => $values['amount'] * env('TAX_RATE', 0.05),
            ))->save();

            return json_encode(array(
                    'success' => true
            ));
        } else {
            // Edit existing bill
            $bill = Bill::where('number', '=', $values['number'])->firstOrFail();
            //Nice error handling?

            $bill->date = $values['date'];
            $bill->description = $values['date'];
            $bill->ref_id = isset($values['ref_id']) ? $values['ref_id'] : null;
            $bill->payment_id = $values['payment_id'];
            $bill->amount = $values['amount'];
            $bill->int_amount = $values['int_amount'];
            $bill->driver_amount = $values['driver_amount'];
            $bill->taxes = $values['amount'] * env('TAX_RATE', 0.05);

            $bill->save();

            return json_encode(array(
                    'success' => true
            ));
        }
    }

    public function create() {
        //Check user settings and return popout or inline based on that
        //Check permissions
        return view('bills.bill_popout', array(
                'source' => 'Create',
                'action' => '/bills'
        ));
    }

    public function edit($id) {
        //Check user settings and return popout or inline based on that
        //Check permissions
        $bill = Bill::where('number', '=', $id)->firstOrFail();
        //Fail a little nicer...
        return view('bills.bill_popout', array(
                'source' => 'Edit',
                'action' => '/bills',
                'bill' => $bill
        ));
    }
}
