<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Models;
use App\Http\Collectors;
use App\Http\Models\Payment;
use \App\Http\Validation\Utils;
use \Stripe;

class PaymentController extends Controller {
    public function DeletePaymentMethod(Request $req, $accountId) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($accountId);

        if($req->user()->cannot('updatePaymentMethods', $account))
            abort(403);

        $paymentMethod = $account->findPaymentMethod($req->payment_method_id);
        $defaultPaymentMethod = $account->defaultPaymentMethod();
        if($paymentMethod->id === $defaultPaymentMethod->id)
            abort(403, 'Unable to delete the default payment method. Please select a new default, and try again');

        $paymentMethod->delete();

        return response()->json(['success' => true]);
    }

    public function GetAccountPaymentMethods(Request $req, $accountId) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($accountId);

        if($req->user()->cannot('updatePaymentMethods', $account))
            abort(403);

        $paymentModelFactory = new Payment\PaymentModelFactory();
        $paymentMethods = $paymentModelFactory->GetAccountStripePaymentMethods($account);

        return response()->json([
            'success' => true,
            'payment_methods' => $paymentMethods,
        ]);
    }

    public function GetModelByAccountId(Request $req, $accountId) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($accountId);
        if($req->user()->cannot('viewPayments', $account))
            abort(403);

        $paymentModelFactory = new Models\Payment\PaymentModelFactory();
        $paymentModel = $paymentModelFactory->GetModelByAccountId($accountId);

        return json_encode($paymentModel);
    }

    public function GetReceivePaymentModel(Request $req, $accountId) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($accountId);

        if($req->user()->cannot('create', Payment::class))
            abort(403);

        $paymentModelFactory = new Models\Payment\PaymentModelFactory();

        return json_encode($paymentModelFactory->GetReceivePaymentModel($account));
    }

    public function GetSetupIntent(Request $req, $accountId) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($accountId);

        if($req->user()->cannot('updatePaymentMethods', $account))
            abort(403);

        $stripeId = $account->createOrGetStripeCustomer();
        $setupIntent = $account->createSetupIntent(['customer' => $stripeId]);

        return response()->json([
            'success' => true,
            'client_secret' => $setupIntent->client_secret
        ]);
    }

    public function ProcessAccountPayment(Request $req) {
        if($req->user()->cannot('create', Payment::class))
            abort(403);

        DB::beginTransaction();

        $accountRepo = new Repos\AccountRepo();
        $invoiceRepo = new Repos\InvoiceRepo();
        $paymentRepo = new Repos\PaymentRepo();

        $paymentCollector = new Collectors\PaymentCollector();
        $paymentValidation = new \App\Http\Validation\PaymentValidationRules();

        $accountPaymentTypeId = $paymentRepo->GetPaymentTypeByName('Account')->payment_type_id;

        $temp = $paymentValidation->GetPaymentOnAccountRules($req);
        $orderIdentifier = '';

        $this->validate($req, $temp['rules'], $temp['messages']);

        $account = $accountRepo->GetById($req->account_id);

        if($req->payment_type_id == $accountPaymentTypeId)
            $accountAdjustment = -(float)$req->payment_amount;
        else
            $accountAdjustment = (float)$req->payment_amount;


        foreach($req->outstanding_invoices as $outstandingInvoice) {
            if($outstandingInvoice['payment_amount'] && $outstandingInvoice['payment_amount'] > 0 && $invoiceRepo->GetById($outstandingInvoice['invoice_id'])->balance_owing > 0) {
                $orderIdentifier .= '_' . $outstandingInvoice['invoice_id'];

                $paymentRepo->insert($paymentCollector->CollectInvoicePayment($req, $outstandingInvoice));
                $invoiceRepo->AdjustBalanceOwing($outstandingInvoice['invoice_id'], -$outstandingInvoice['payment_amount']);
                if($req->payment_type_id != $accountPaymentTypeId)
                    $accountAdjustment -= $outstandingInvoice['payment_amount'];
            }
        }
        if(!floatval($accountAdjustment) == 0) {
            $accountRepo->AdjustBalance($req->account_id, $accountAdjustment);
            $comment = floatval($accountAdjustment > 0) ? 'Account credit applied' : 'Payment made from account balance';
            $paymentRepo->insert($paymentCollector->CollectAccountPayment($req, $accountAdjustment, $comment));
        }

        if($req->payment_method_id) {
            $stripe = new Stripe\StripeClient(env('STRIPE_SECRET'));
            $paymentMethod = $account->findPaymentMethod($req->payment_method_id);

            foreach($req->outstanding_invoices as $outstandingInvoice) {
                $stripe->paymentIntents->create([
                    'amount' => (float)$outstandingInvoice['payment_amount'] * 100,
                    'confirm' => true,
                    'currency' => env('CASHIER_CURRENCY'),
                    'customer' => $account->stripe_id,
                    'description' => 'Payment on FastForward Invoice #' . $outstandingInvoice['invoice_id'],
                    'payment_method' => $paymentMethod->id,
                ]);
            }
        }

        DB::commit();
        return response()->json(['success' => true]);
    }

    public function SetDefaultPaymentMethod(Request $req, $accountId) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($accountId);

        if($req->user()->cannot('updatePaymentMethods', $account))
            abort(403);

        $paymentMethods = $account->paymentMethods();
        $newDefault = $account->findPaymentMethod($req->payment_method_id);

        $account->updateDefaultPaymentMethod($req->payment_method_id);

        return response()->json([
            'success' => true
        ]);
    }
}
?>
