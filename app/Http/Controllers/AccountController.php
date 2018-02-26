<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Models\Account;
use \App\Http\Validation\Utils;


class AccountController extends Controller {

    public function __construct() {
        $this->middleware('auth');

        //API STUFF
        $this->sortBy = 'name';
        $this->maxCount = env('DEFAULT_CUSTOMER_COUNT', $this->maxCount);
        $this->itemAge = env('DEFAULT_CUSTOMER_AGE', '6 month');
        $this->class = new \App\Account;
    }

    public function index(Request $req) {
        return view('accounts.accounts');
    }

    public function buildTable(Request $req) {
        $accountModelFactory = new Account\AccountModelFactory();
        $model = $accountModelFactory->ListAll($req);
        return json_encode($model);
    }

    public function create() {
        //Check user settings and return popout or inline based on that
        //Check permissions
        $amf = new Account\AccountModelFactory();
        $model = $amf->GetCreateModel();
        return view('accounts.account', compact('model'));
    }

    public function edit($id) {
        $factory = new Account\AccountModelFactory();
        $model = $factory->GetEditModel($id);
        return view('accounts.account', compact('model'));
    }

    public function store(Request $req) {
        DB::beginTransaction();
        try {
            $partialsRules = new \App\Http\Validation\PartialsValidationRules();
            $accountsRules = new \App\Http\Validation\AccountValidationRules();
            $contactCollector = new \App\Http\Collectors\ContactCollector();
            $contactsCollector = new \App\Http\Collectors\ContactsCollector();

            $acctRules = $accountsRules->GetValidationRules($req->input('account-number') !== null && $req->input('account-id') !== null && $req->input('account-id') > 0,
                $req->input('account-id'), $req->input('account-number'), $req->input('isSubLocation') == 'true', $req->input('shouldGiveDiscount') == 'true',
                $req->input('useCustomField') == 'true');

            $validationRules = $acctRules['rules'];
            $validationMessages = $acctRules['messages'];

            $addrRules = $partialsRules->GetAddressValidationRules('delivery', 'Delivery');
            $validationRules = array_merge($validationRules, $addrRules['rules']);
            $validationMessages = array_merge($validationMessages, $addrRules['messages']);

            $contacts = $contactsCollector->collectAll($req, 'account', false);

            if (count($contacts) < 1) {
                //Manually fail validation
                $rules['Contacts'] = 'required';
                $validator =  \Illuminate\Support\Facades\Validator::make($req->all(), $rules);
                if ($validator->fails()) return redirect()->back()->withErrors($validator)->withInput();
            }

            $contactsVal = $partialsRules->GetContactsValidationRules($req, $contacts, false);
            $validationRules = array_merge($validationRules, $contactsVal['rules']);
            $validationMessages = array_merge($validationMessages, $contactsVal['messages']);

            if ($req->input('billing-address') == 'on') {
                $billAddressRules = $partialsRules->GetAddressValidationRules('billing', 'Billing');
                $validationRules = array_merge($validationRules, $billAddressRules['rules']);
                $validationMessages = array_merge($validationMessages, $billAddressRules['messages']);
            }

            for ($i = 1; $i < 3; $i++) {
                if ($req->input('give-commission-' . $i) == 'true') {
                    $valRules = $partialsRules->GetCommissionValidationRules('commission-' . $i, 'Commission', ($req->input('depreciate-' . $i . '-percent') != '' || $req->input('depreciate-' . $i . '-duration') != '' || $req->input('depreciate-' . $i . '-start-date') != ''));
                    $validationRules = array_merge($validationRules, $valRules['rules']);
                    $validationMessages = array_merge($validationMessages, $valRules['messages']);
                }
            }

            $this->validate($req, $validationRules, $validationMessages);

            $accountRepo = new Repos\AccountRepo();
            $contactRepo = new Repos\ContactRepo();
            $addressRepo = new Repos\AddressRepo();
            $emailAddressRepo = new Repos\EmailAddressRepo();
            $phoneNumberRepo = new Repos\PhoneNumberRepo();
            $commissionRepo = new Repos\CommissionRepo();

            //BEGIN delivery address
            $addrCollector = new \App\Http\Collectors\AddressCollector();
            $delivery = $addrCollector->CollectForAccount($req, 'delivery');
            $delivery_id = $delivery["address_id"];

            if ($delivery_id == null)
                $delivery_id = $addressRepo->Insert($delivery)->address_id;
            else
                $addressRepo->Update($delivery);

            //END delivery address
            //BEGIN billing address
            $billing_id = $req->input('billing-id');
            if ($req->input('billing-address') == 'on') {
                $billing = $addrCollector->CollectForAccount($req, 'billing');

                if ($billing_id == null || $billing_id == '')
                    $billing_id = $addressRepo->Insert($billing)->address_id;
                else
                    $addressRepo->Update($billing);
            } else if ($billing_id != null || $billing_id != '')
                    $addressRepo->Delete($billing_id);

            if ($billing_id == '')
                $billing_id = null;
            //END billing address
            //BEGIN account
            $acctCollector = new \App\Http\Collectors\AccountCollector();
            $account = $acctCollector->Collect($req, $billing_id, $delivery_id);

            $accountId = $req->input('account-id');
    		$isNew = $accountId == null;
    		$args = [];
            if ($isNew) {
                $accountId = $accountRepo->Insert($account)->account_id;
                $action = 'AccountController@create';
            }
            else {
                $account["account_id"] = $accountId;
                $accountRepo->Update($account);
                $action = 'AccountController@edit';
                $args = ['id' => $accountId];
            }

            //Commission
            $commissionCollector = new \App\Http\Collectors\CommissionCollector();
            $commission1 = $commission2 = null;

            if ($req->input('give-commission-1') == 'true')
                $commission1 = $commissionCollector->Collect($req, 'commission-1', $accountId);

            if ($req->input('give-commission-2') == 'true')
                $commission2 = $commissionCollector->Collect($req, 'commission-2', $accountId);

            if ($isNew) {
                if ($commission1 !== null)
                    $commissionRepo->Insert($commission1);

                if ($commission2 !== null)
                    $commissionRepo->Insert($commission2);
            } else {
                if ($req->input('give-commission-1') == 'true') {
                    if ($commission1->commission_id == null)
                        $commissionRepo->Insert($commission1);
                    else
                        $commissionRepo->Update($commission1);
                } else if ($commission1 != null)
                    $commissionRepo->Delete($commission1->commission_id);

                if ($req->input('give-commission-2') == 'true') {
                    if ($commission2->commission_id == null)
                        $commissionRepo->Insert($commission2);
                    else
                        $commissionRepo->Update($commission2);
                } else if ($commission2 != null)
                    $commissionRepo->Delete($commission2->commission_id);
            }

            //BEGIN contacts
            $newPrimaryId = $req->input('account-current-primary');
            $contactRepo->HandleAccountContacts($contacts, $accountId, $newPrimaryId);
            //END contacts
            
            DB::commit();
            //END account

            return response()->json([
                'success' => true,
            ]);

        } catch(Exception $e) {
            DB::rollBack();
            dd(response());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function deactivate($account_id) {
        $accountRepo = new Repos\AccountRepo();

        $accountRepo->Deactivate($account_id);

        return;
    }

    public function activate($account_id) {
        $accountRepo = new Repos\AccountRepo();

        $accountRepo->Activate($account_id);

        return;
    }

    public function is_unique(Request $req) {
        try {
            $number = $req->input('number');
            if (!isset($number) || strlen($number) <= 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Account Number was not specified.'
                ]);
            }

            $acctRepo = new Repos\AccountRepo();
            $unique = $acctRepo->IsUnique($number);

            return response()->json([
                'success' => true,
                'accounts' => $unique
            ]);
        } catch(Exception $e){
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
