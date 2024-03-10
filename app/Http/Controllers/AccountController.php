<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;

use App\Models\Account;
use App\Http\Models;
use App\Http\Models\Chart;
use App\Http\Models\Permission;
use App\Http\Validation;
use App\Http\Validation\Utils;

use Illuminate\Support\Facades\Auth;

use App\Models\AccountInvoiceSortOrder;

class AccountController extends Controller {
    public function __construct() {
        $this->middleware('auth');

        //API STUFF
        $this->sortBy = 'name';
        $this->maxCount = env('DEFAULT_CUSTOMER_COUNT', $this->maxCount);
        $this->itemAge = env('DEFAULT_CUSTOMER_AGE', '6 month');
        $this->class = new Account;

        $count = AccountInvoiceSortOrder::all()->count();
        if($count == 0) {
            $accountRepo = new Repos\AccountRepo();

            $accounts = $accountRepo->ListAll(Auth::user());
            foreach($accounts as $account) {
                $accountRepo->ConvertInvoiceSortOrder($account->account_id);
            }
        }
    }

    public function adjustAccountCredit(Request $req) {
        if($req->user()->cannot('payments.edit.*'))
            abort(403);

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

    public function getBillingModel(Request $req, $accountId) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($accountId);
        if($req->user()->cannot('viewPayments', $account))
            abort(403);

        $accountModelFactory = new Models\Account\AccountModelFactory();
        $billingModel = $accountModelFactory->GetBillingModel($accountId);

        return json_encode($billingModel);
    }

    public function getChart(Request $req) {
        $accountRepo = new Repos\AccountRepo();
        $chartModelFactory = new Chart\ChartModelFactory();

        $account = $accountRepo->GetById($req->account_id);
        if($req->user()->cannot('viewPayments', $account))
            abort(403);

        $filterBy = ['column' => 'charge_account_id', 'value' => $req->account_id];
        $bills = $chartModelFactory->GetMonthlyBills('month', $req->start_date, $req->end_date, 'delivery_type', $req->summationType, $filterBy);

        return json_encode($bills);
    }

    public function getModel(Request $req, $accountId = null) {
        $accountModelFactory = new Models\Account\AccountModelFactory();
        $accountRepo = new Repos\AccountRepo();

        // If we are requesting an account id, we mean to edit, if not then create
        if($accountId) {
            $accountId = strtoupper($accountId);
            if($accountId[0] === 'N') {
                $accountId = substr($accountId, 1);
                $accountId = $accountRepo->GetAccountIdByAccountNumber($accountId);
            }

            $account = $accountRepo->GetById($accountId);

            if($accountId && $req->user()->cannot('view', $account))
                abort(403);

            $permissionModelFactory = new Permission\PermissionModelFactory();
            $permissions = $permissionModelFactory->getAccountPermissions($req->user(), $account);

            $model = $accountModelFactory->GetEditModel($accountId, $permissions);
        } else {
            if(!$accountId && $req->user()->cannot('create', Account::class))
                abort(403);

            $model = $accountModelFactory->GetCreateModel(['create' => true]);
        }

        return json_encode($model);
    }

    public function getShippingAddress(Request $req) {
        $addressRepo = new Repos\AddressRepo();
        $accountRepo = new Repos\AccountRepo();

        $account = $accountRepo->GetById($req->account_id);

        if($req->user()->cannot('view', $account))
            abort(403);

        return $addressRepo->GetById($account->shipping_address_id);
    }

    public function index(Request $req) {
        $user = $req->user();
        if($user->cannot('viewAny', Account::class))
            abort(403);

        $accountRepo = new Repos\AccountRepo();
        $queryRepo = new Repos\QueryRepo();

        if(count($user->accountUsers) > 0)
            $model = $accountRepo->ListAll($user, $user->can('viewChildAccounts', $accountRepo->GetById($user->accountUsers[0]->account_id)));
        else if($user->employee || $user->hasRole('superAdmin'))
            $model = $accountRepo->ListAll(null);

        $queries = $queryRepo->GetByTable('accounts');

        return response()->json([
            'success' => true,
            'data' => $model,
            'queries' => $queries
        ]);
    }

    public function store(Request $req) {
        DB::beginTransaction();

        $partialsRules = new Validation\PartialsValidationRules();
        $accountsRules = new Validation\AccountValidationRules();

        $accountRepo = new Repos\AccountRepo();
        $addressRepo = new Repos\AddressRepo();
        $permissionModelFactory = new Permission\PermissionModelFactory();

        $oldAccount = $accountRepo->GetById($req->account_id);
        $accountPermissions = $permissionModelFactory->getAccountPermissions($req->user(), $oldAccount);

        if($oldAccount) {
            //Can I edit this?
            $user = $req->user();
            if (!$accountPermissions['editAdvanced'] && !$accountPermissions['editBasic'] && !$accountPermissions['editInvoicing'])
                abort(403);
        } else {
            if(!$accountPermissions['create'])
                abort(403);
        }

        $acctRules = $accountsRules->GetValidationRules($req, $accountPermissions);
        $validationRules = $acctRules['rules'];
        $validationMessages = $acctRules['messages'];

        if($oldAccount ? $accountPermissions['editBasic'] : $accountPermissions['create']) {
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

            //BEGIN shipping address
            $addrCollector = new \App\Http\Collectors\AddressCollector();
            $shipping = $addrCollector->collectWithPrefix($req, 'shipping_address', $oldAccount ? $oldAccount->shipping_address_id : null);
            $shippingId = $shipping["address_id"];

            if ($shippingId)
                $addressRepo->UpdateMinimal($shipping);
            else
                $shippingId = $addressRepo->InsertMinimal($shipping)->address_id;
            //END shipping address
            //BEGIN billing address
            if ($useSeparateBillingAddress) {
                $billing = $addrCollector->collectWithPrefix($req, 'billing_address', $oldAccount ? $oldAccount->billing_address_id : null);

                if ($billing['address_id'])
                    $billingId = $addressRepo->UpdateMinimal($billing)->address_id;
                else
                    $billingId = $addressRepo->InsertMinimal($billing)->address_id;
            } else {
                $billingId = null;
            }
            //END billing address
        }

        //BEGIN account
        $accountCollector = new \App\Http\Collectors\AccountCollector();
        $account = $accountCollector->Collect($req, $shippingId, $billingId, $accountPermissions);

        $accountId = $req->input('account_id');
        if ($oldAccount)
            $accountRepo->Update($account, $accountPermissions);
        else
            $accountId = $accountRepo->Insert($account)->account_id;
        // Due to foreign key constraints, we check whether the billing address needs to be deleted AFTER updating the account and setting it to NULL
        if ($oldAccount && !$useSeparateBillingAddress && $oldAccount->billing_address_id != null)
            $addressRepo->Delete($oldAccount->billing_address_id);

        //BEGIN invoice sort order
        if ($accountPermissions['editInvoicing']) {
            $invoiceSortOrder = $accountCollector->CollectInvoiceSortOrder($req, $accountId);
            $accountRepo->StoreInvoiceSortOrder($invoiceSortOrder);
        }

        DB::commit();
        //END account

        return response()->json([
            'success' => true,
            'account_id' => $accountId
        ]);
    }

    public function toggleActive(Request $req, $accountId) {
        $accountRepo = new Repos\AccountRepo();

        if($req->user()->cannot('toggleEnabled', $accountRepo->GetById($accountId)))
            abort(403);

        $accountRepo->ToggleActive($accountId);
    }
}
