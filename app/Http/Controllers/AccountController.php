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

    public function getModel($accountId = null) {
        //Check permissions
        $accountModelFactory = new Account\AccountModelFactory();
        if($accountId) {
            $accountId = strtoupper($accountId);
            if($accountId[0] === 'N') {
                $accountRepo = new Repos\AccountRepo();
                $accountId = substr($accountId, 1);
                $accountId = $accountRepo->GetAccountIdByAccountNumber($accountId);
            }
            $model = $accountModelFactory->GetEditModel($accountId);
        } else
            $model = $accountModelFactory->GetCreateModel();
        return json_encode($model);
    }

    public function adjustAccountCredit(Request $req) {
        DB::beginTransaction();

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
        $newBalance = $accountRepo->AdjustBalance($req->account_id, $req->credit_amount)->account_balance;
        $paymentRepo->Insert($accountCreditPayment);

        DB::commit();
        return response()->json([
            'success' => true,
            'new_account_balance' => $newBalance
        ]);
    }

    public function store(Request $req) {
        DB::beginTransaction();

        $partialsRules = new Validation\PartialsValidationRules();
        $accountsRules = new Validation\AccountValidationRules();

        if($req->account_id == '') {
            //Can I create this?
            $isEdit = false;
        } else {
            //Can I edit this?
            $isEdit = true;
        }

        $acctRules = $accountsRules->GetValidationRules($req);
        $validationRules = $acctRules['rules'];
        $validationMessages = $acctRules['messages'];

        $addrRules = $partialsRules->GetAddressMinValidationRules($req, 'shipping_address', 'Shipping');
        $validationRules = array_merge($validationRules, $addrRules['rules']);
        $validationMessages = array_merge($validationMessages, $addrRules['messages']);

        $useSeparateBillingAddress = !filter_var($req->use_shipping_for_billing_address, FILTER_VALIDATE_BOOLEAN);

        if ($useSeparateBillingAddress) {
            $billingAddressRules = $partialsRules->GetAddressMinValidationRules($req, 'billing_address', 'Billing');
            $validationRules = array_merge($validationRules, $billingAddressRules['rules']);
            $validationMessages = array_merge($validationMessages, $billingAddressRules['messages']);
        }

        $this->validate($req, $validationRules, $validationMessages);

        $accountRepo = new Repos\AccountRepo();
        $addressRepo = new Repos\AddressRepo();

        $oldAccount = $accountRepo->GetById($req->account_id);

        //BEGIN shipping address
        $addrCollector = new \App\Http\Collectors\AddressCollector();
        $shipping = $addrCollector->CollectMinimal($req, 'shipping_address', $oldAccount ? $oldAccount->shipping_address_id : null);
        $shippingId = $shipping["address_id"];

        if ($shippingId == null)
            $shippingId = $addressRepo->InsertMinimal($shipping)->address_id;
        else
            $addressRepo->UpdateMinimal($shipping);
        //END shipping address
        //BEGIN billing address
        if ($useSeparateBillingAddress) {
            $billing = $addrCollector->CollectMinimal($req, 'billing_address', $oldAccount ? $oldAccount->billing_address_id : null);

            if ($billing['address_id'])
                $billingId = $addressRepo->UpdateMinimal($billing)->address_id;
            else
                $billingId = $addressRepo->InsertMinimal($billing)->address_id;
        } else {
            if ($oldAccount && $oldAccount->billing_address_id != null)
                $addressRepo->Delete($billingId);
            $billingId = null;
        }
        //END billing address
        //BEGIN account
        $acctCollector = new \App\Http\Collectors\AccountCollector();
        $account = $acctCollector->Collect($req, $shippingId, $billingId);

        $accountId = $req->input('account_id');
        if ($isEdit)
            $accountRepo->Update($account);
        else
            $accountId = $accountRepo->Insert($account)->account_id;

        DB::commit();
        //END account

        return response()->json([
            'success' => true,
            'account_id' => $accountId
        ]);
    }

    public function toggleActive($accountId) {
        $accountRepo = new Repos\AccountRepo();

        $accountRepo->ToggleActive($accountId);
        return response()->json([
            'success' => true
        ]);
    }

    public function getShippingAddress(Request $req) {
        $addressRepo = new Repos\AddressRepo();
        $accountRepo = new Repos\AccountRepo();

        return $addressRepo->GetById(($accountRepo->GetById($req->account_id))['shipping_address_id']);
    }
}
