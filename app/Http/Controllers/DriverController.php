<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Repos;
use App\Http\Models\Driver;

class DriverController extends Controller {
    public function __construct() {
        $this->middleware('auth');

        //API STUFF
        $this->sortBy = 'name';
        $this->maxCount = env('DEFAULT_DRIVER_COUNT', $this->maxCount);
        $this->itemAge = env('DEFAULT_DRIVER_AGE', '1 month');
        $this->class = new \App\Driver;
    }

    public function index() {
        $factory = new Driver\DriverModelFactory();
        $contents = $factory->ListAll();

        return view('drivers.drivers', compact('contents'));
    }

    public function create(){

        return view('drivers.create_driver');
    }

    public function store(Request $req) {
        $validationRules = [
            'first_name' => 'required',
            'last_name' => 'required',
            'email_address' => 'required|email',
            'email_address2' => 'email',
            //Regex used found here: http://www.regexlib.com/REDetails.aspx?regexp_id=607
            'primary_phone' => ['required', 'regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
            'secondary_phone' => ['regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
            'pager_number' => ['required', 'regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
            'address1' => 'required',
            //Regex used found here: http://regexlib.com/REDetails.aspx?regexp_id=417
            'postal_code' => ['required', 'regex:/^((\d{5}-\d{4})|(\d{5})|([AaBbCcEeGgHhJjKkLlMmNnPpRrSsTtVvXxYy]\d[A-Za-z]\s?\d[A-Za-z]\d))$/'],
            'city' => 'required',
            'province' => 'required',
            'country' => 'required',

            'emerg_first_name' => 'required',
            'emerg_last_name' => 'required',
            'emerg_email_address' => 'required|email',
            'emerg_email_address2' => 'email',
            'emerg_primary_phone' => ['required', 'regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
            'emerg_secondary_phone' => ['regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/'],
            'emerg_address1' => 'required',
            'emerg_postal_code' => ['required', 'regex:/^((\d{5}-\d{4})|(\d{5})|([AaBbCcEeGgHhJjKkLlMmNnPpRrSsTtVvXxYy]\d[A-Za-z]\s?\d[A-Za-z]\d))$/'],
            'emerg_city' => 'required',
            'emerg_province' => 'required',
            'emerg_country' => 'required',

            'DLN' => 'required',
            'license_plate' => ['required', 'regex:/([A-Z]{3}-[0-9]{4})|([B-WY][A-Z]{2}-[0-9]{3})|([1-9]-[0-9]{5})|([B-DF-HJ-NP-TV-XZ]-[0-9]{5})|([0-9]{2}-[A-Z][0-9]{3})/'],
            'insurance' => 'required',
            'license_expiration' => 'required|date',
            'license_plate_expiration' => 'required|date',
            'insurance_expiration' => 'required|date',
            'SIN' => ['required', 'regex:/[0-9]{3} [0-9]{3} [0-9]{3}/'],
            'startdate' => 'required|date',
            'DOB' => 'required|date'
        ];

        $validationMessages = [
            'first_name.required' => 'First Name is required.',
            'last_name.required' => 'Last Name is required.',
            'email_address.required' => 'Email Address is required.',
            'email_address.email' => 'Email address must be in the form "someone@example.com"',
            'email_address2.email' => 'Secondary Email Address must be in the form "someone@example.com"',
            'primary_phone.required' => "Primary Phone Number is required.",
            'primary_phone.regex' => 'Primary Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
            'secondary_phone.regex' => 'Secondary Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
            'pager_number.required' => 'Pager Number is required.',
            'pager_number.regex' => 'Pager Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
            'address1.required' => 'Home Address is required.',
            'postal_code.required' => 'Home Address Postal Code is required.',
            'postal_code.regex' => 'Home Address Postal Code must be in the format "Q4B 5C5", "501-342", or "123324".',
            'city.required' => 'Home Address City is required.',
            'province.required' => 'Home Address Province is required.',
            'country.required' => 'Home Address Country is required.',

            'emerg_first_name.required' => 'Emergency Contact First Name is required.',
            'emerg_last_name.required' => 'Emergency Contact Last Name is required.',
            'emerg_email_address.required' => 'Emergency Contact Email is required',
            'emerg_email_address.email' => 'Emergency Contact Email must be in the format "someone@example.com"',
            'emerg_email_address2.email' => 'Emergency Contact Secondary Email must be in the format "someone@example.com"',
            'emerg_primary_phone.required' => 'Emergency Contact Primary Phone Number is required.',
            'emerg_primary_phone.regex' => 'Emergency Contact Primary Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
            'emerg_secondary_phone.regex' => 'Emergency Contact Secondary Phone Number must be in the format "5305551212", "(530) 555-1212", or "530-555-1212".',
            'emerg_address1.required' => 'Emergency Contact Address is required.',
            'emerg_postal_code.required' => 'Emergency Contact Address Postal Code is required.',
            'emerg_postal_code.regex' => 'Emergency Contact Address Postal Code must be in the format "Q4B 5C5", "501-342", or "123324".',
            'emerg_city.required' => 'Emergency Contact Address City is required.',
            'emerg_province.required' => 'Emergency Contact Address Province is required.',
            'emerg_country.required' => 'Emergency Contact Address Country is required.',

            'DLN.required' => 'Driver License Number is required.',
            'license_plate.required' => 'License Plate is required.',
            'license_plate.regex' => 'License Plate must be in the format "AAA-####", "AAA-###", "#-#####", "A-#####", or "##-A###"',
            'insurance.required' => 'Insurance Number is required.',
            'license_expiration.required' => 'Drivers License Expiration Date is required.',
            'license_expiration.date' => 'Drivers License Expiration Date must be a date.',
            'license_plate_expiration.required' => 'License Plate Expiration Date is required.',
            'license_plate_expiration.date' => 'License Plate Expiration Date must be a date.',
            'insurance_expiration.required' => 'Insurance Expiration Date is required.',
            'insurance_expiration.date' => 'Insurance Expiration Date must be a date.',
            'SIN.required' => 'SIN is required.',
            'SIN.regex' => 'SIN must be in the format "### ### ###"',
            'startdate.required' => 'Start Date is required.',
            'startdate.date' => 'Start Date must be a date.',
            'DOB.required' => 'Date of Birth is required.',
            'DOB.date' => 'Date of Birth must be a date.',
        ];

        $this->validate($req, $validationRules, $validationMessages);

        $user = [
            'username' => substr($req->input('first_name'), 0, 1) . $req->input('last_name'),
            'email' => $req->input('email_address'),
        ];

        $contact = [
            'first_name' => $req->input('first_name'),
            'last_name' => $req->input('last_name'),
            'is_primary' => true
        ];

        $eContact = [
            'first_name' => $req->input('emerg_first_name'),
            'last_name' => $req->input('emerg_last_name'),
            'is_primary' => true
        ];

        $userRepo = new Repos\UserRepo();
        $pnRepo = new Repos\PhoneNumberRepo();
        $eAddrRepo = new Repos\EmailAddressRepo();
        $addrRepo = new Repos\AddressRepo();
        $contactRepo = new Repos\ContactRepo();
        $driverRepo = new Repos\DriverRepo();

        //Contact Info/User
        $userId = $userRepo->Insert($user, 'Driver')->user_id;
        $contactId = $contactRepo->Insert($contact)->contact_id;

        $primaryPhone = [
            'type' => 'primary',
            'phone_number' => $req->input('primary_phone'),
            'is_primary' => true,
            'contact_id' => $contactId
        ];
        $pnRepo->Insert($primaryPhone);

        if ($req->has('secondary_phone') && strlen($req->input('secondary_phone')) > 0) {
            $secondaryPhone = [
                'type' => 'secondary',
                'phone_number' => $req->input('secondary_phone'),
                'is_primary' => false,
                'contact_id' => $contactId
            ];
            $pnRepo->Insert($secondaryPhone);
        }

        $pager = [
            'type' => 'pager',
            'phone_number' => $req->input('pager_number'),
            'is_primary' => false,
            'contact_id' => $contactId
        ];
        $pnRepo->Insert($pager);

        $emailAddress = [
            'type' => 'primary',
            'email' => $req->input('email_address'),
            'is_primary' => true,
            'contact_id' => $contactId
        ];
        $eAddrRepo->Insert($emailAddress);

        if ($req->has('email_address2') && strlen($req->input('email_address2')) > 0) {
            $emailAddress2 = [
                'type' => 'primary',
                'email' => $req->input('email_address2'),
                'is_primary' => false,
                'contact_id' => $contactId
            ];
            $eAddrRepo->Insert($emailAddress2);
        }

        $addr = [
            'street' => $req->input('address1'),
            'street2' => $req->input('address2'),
            'city' => $req->input('city'),
            'zip_postal' => $req->input('postal_code'),
            'state_province' => $req->input('province'),
            'country' => $req->input('country'),
            'is_primary' => true,
            'contact_id' => $contactId
        ];
        $addrRepo->Insert($addr);

        //Emergency Contact
        $eContactId = $contactRepo->Insert($eContact)->contact_id;

        $ePrimaryPhone = [
            'type' => 'primary',
            'phone_number' => $req->input('emerg_primary_phone'),
            'is_primary' => true,
            'contact_id' => $eContactId
        ];
        $pnRepo->Insert($ePrimaryPhone);

        if ($req->has('emerg_secondary_phone') && strlen($req->input('emerg_secondary_phone')) > 0) {
            $eSecondaryPhone = [
                'type' => 'secondary',
                'phone_number' => $req->input('emerg_secondary_phone'),
                'is_primary' => false,
                'contact_id' => $eContactId
            ];
            $pnRepo->Insert($eSecondaryPhone);
        }

        $eEmailAddress = [
            'type' => 'primary',
            'email' => $req->input('emerg_email_address'),
            'is_primary' => true,
            'contact_id' => $eContactId
        ];
        $eAddrRepo->Insert($eEmailAddress);

        if ($req->has('emerg_email_address2') && strlen($req->input('emerg_email_address2')) > 0) {
            $eEmailAddress2 = [
                'type' => 'primary',
                'email' => $req->input('emerg_email_address2'),
                'is_primary' => false,
                'contact_id' => $eContactId
            ];
            $eAddrRepo->Insert($eEmailAddress2);
        }

        $eAddr = [
            'street' => $req->input('address1'),
            'street2' => $req->input('address2'),
            'city' => $req->input('city'),
            'zip_postal' => $req->input('postal_code'),
            'state_province' => $req->input('province'),
            'country' => $req->input('country'),
            'is_primary' => true,
            'contact_id' => $eContactId
        ];
        $addrRepo->Insert($eAddr);

        $driver = [
            'contact_id' => $contactId,
            'user_id' => $userId,
            'driver_number' => null,
            'stripe_id' => null,
            'start_date' => new \DateTime($req->input('startdate')),
            'drivers_license_number' => $req->input('DLN'),
            'license_expiration' => new \DateTime($req->input('license_expiration')),
            'license_plate_number' => $req->input('license_plate'),
            'license_plate_expiration' => new \DateTime($req->input('license_plate_expiration')),
            'insurance_number' => $req->input('insurance'),
            'insurance_expiration' => new \DateTime($req->input('insurance_expiration')),
            'sin' => $req->input('SIN'),
            'dob' => new \DateTime($req->input('DOB')),
            'active' => $req->input('active') == 'on',
            'pickup_commission' => 0.15,
            'delivery_commission' => 0.15,
        ];
        $driverRepo->Insert($driver);

        return redirect()->action('DriverController@create');
    }

    public function edit($id) {
        $repo = new Repos\DriversRepo();

        $model = $repo->GetById($id);

        return view('drivers.edit_driver', compact('model'));
    }

    public function submitEdit() {
        return redirect()->action('DriverController@edit');
    }

    protected function genFilterData($input) {
        return null;
    }
}
