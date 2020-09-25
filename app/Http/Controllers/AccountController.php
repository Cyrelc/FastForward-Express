<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Models\Account;
use \App\Http\Validation\Utils;
use \App\Http\Validation;

class AccountController extends Controller {

    public function __construct() {
        $this->middleware('auth');

        //API STUFF
        $this->sortBy = 'name';
        $this->maxCount = env('DEFAULT_CUSTOMER_COUNT', $this->maxCount);
        $this->itemAge = env('DEFAULT_CUSTOMER_AGE', '6 month');
        $this->class = new \App\Account;
    }

    public function buildTable(Request $req) {
        $accountModelFactory = new Account\AccountModelFactory();
        $model = $accountModelFactory->ListAll($req);
        return json_encode($model);
    }

    public function create() {
        //Check permissions
        $modelFactory = new Account\AccountModelFactory();
        $model = $modelFactory->GetCreateModel();
        return view('accounts.account', compact('model'));
    }

    public function edit($id) {
        $id = strtoupper($id);
        if($id[0] === 'N') {
            $id = substr($id, 1);
            $accountRepo = new Repos\AccountRepo();
            $id = $accountRepo->GetAccountIdByAccountNumber($id);
        }
        $factory = new Account\AccountModelFactory();
        $model = $factory->GetEditModel($id);
        return view('accounts.account', compact('model'));
    }

    public function giveAccountCredit(Request $req) {
        DB::beginTransaction();
        try {
            $accountValidationRules = new Validation\AccountValidationRules();

            $validationRules = [];
            $validationMessages = [];

            $creditRules = $accountValidationRules->GetAccountCreditRules($req);
            $validationRules = $creditRules['rules'];
            $validationMessages = $creditRules['messages'];

            $this->validate($req, $validationRules, $validationMessages);

            $accountRepo = new Repos\AccountRepo();
            $paymentCollector = new \App\Http\Collectors\PaymentCollector();
            $paymentRepo = new Repos\PaymentRepo();

            $accountCreditPayment = $paymentCollector->CollectAccountCredit($req);
            $accountRepo->AdjustBalance($req->account_id, $req->credit_amount);
            $paymentRepo->Insert($accountCreditPayment);

            DB::commit();
            return response()->json([
                'success' => true,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function store(Request $req) {
        DB::beginTransaction();
        try {
            $partialsRules = new Validation\PartialsValidationRules();
            $accountsRules = new Validation\AccountValidationRules();

            $validationRules = [];
            $validationMessages = [];

            if($req->account_id == '') {
                //Can I create this?
                $isEdit = false;
            } else {
                //Can I edit this?
                $isEdit = true;
            }

            $acctRules = $accountsRules->GetValidationRules($req);
            $validationRules = array_merge($validationRules, $acctRules['rules']);
            $validationMessages = array_merge($validationMessages, $acctRules['messages']);

            $addrRules = $partialsRules->GetAddressValidationRules($req, 'delivery', 'Delivery');
            $validationRules = array_merge($validationRules, $addrRules['rules']);
            $validationMessages = array_merge($validationMessages, $addrRules['messages']);

            if ($req->use_billing_address == 'on') {
                $billAddressRules = $partialsRules->GetAddressValidationRules($req, 'billing', 'Billing');
                $validationRules = array_merge($validationRules, $billAddressRules['rules']);
                $validationMessages = array_merge($validationMessages, $billAddressRules['messages']);
            }

            $this->validate($req, $validationRules, $validationMessages);

            $accountRepo = new Repos\AccountRepo();
            $addressRepo = new Repos\AddressRepo();
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
            if ($req->use_billing_address == 'on') {
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

            $accountId = $req->input('account_id');
            if ($isEdit) {
                $account["account_id"] = $accountId;
                $accountRepo->Update($account);
                $action = 'AccountController@edit';
                $args = ['id' => $accountId];
            }
            else {
                $req->account_id = $accountRepo->Insert($account)->account_id;
                $userController = new UserController();
                $userController->storeAccountUser($req, true);
                $action = 'AccountController@create';
            }

            DB::commit();
            //END account

            return response()->json([
                'success' => true,
            ]);

        } catch(Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function storeInvoiceLayout(Request $req, $account_id) {
        DB::beginTransaction();
        try{
            $accountRepo = new Repos\AccountRepo();
            $invoiceRepo = new Repos\InvoiceRepo();

            $accountRepo->UpdateInvoiceComment($req->comment, $account_id);
            $invoiceRepo->StoreSortOrder($req, $account_id);

            DB::commit();

            return;
        } catch(Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function toggleActive($account_id) {
        $accountRepo = new Repos\AccountRepo();

        $accountRepo->ToggleActive($account_id);
        return response()->json([
            'success' => true
        ]);
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

    public function getShippingAddress(Request $req) {
        $addressRepo = new Repos\AddressRepo();
        $accountRepo = new Repos\AccountRepo();

        return $addressRepo->GetById(($accountRepo->GetById($req->account_id))['shipping_address_id']);
    }
}
