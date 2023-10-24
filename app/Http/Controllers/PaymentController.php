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

    public function GetPaymentIntent(Request $req) {
        if($req->user()->cannot('create', Payment::class))
            abort(403);

        $paymentCollector = new Collectors\PaymentCollector();
        $paymentValidation = new \App\Http\Validation\PaymentValidationRules();

        $temp = $paymentValidation->GetPaymentIntentRules($req);
        $this->validate($req, $temp['rules'], $temp['messages']);

        $invoiceRepo = new Repos\InvoiceRepo();
        $paymentRepo = new Repos\PaymentRepo();

        $outstandingInvoice = $invoiceRepo->GetById($req->invoice_id);
        $incompletePaymentIntents = $paymentRepo->GetIncompletePaymentIntents($outstandingInvoice->invoice_id);

        $stripe = new Stripe\StripeClient(env('STRIPE_SECRET'));

        // If there is an existing PaymentIntent that has not resolved, use that first do not create multiple database entries
        if(!$incompletePaymentIntents->isEmpty()) {
            $paymentIntent = $stripe->paymentIntents->retrieve($incompletePaymentIntents[0]['payment_intent_id']);
            return json_encode([
                'client_secret' => $paymentIntent->client_secret
            ]);
        }

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => (float)$req->amount * 100,
            'currency' => env('CASHIER_CURRENCY'),
            'description' => 'Payment on FastForward Invoice #' . $outstandingInvoice['invoice_id'],
        ]);
        DB::beginTransaction();
        $paymentRepo->Insert($paymentCollector->CollectInvoicePayment($req, $outstandingInvoice, $paymentIntent));
        DB::commit();

        return json_encode(
            ['client_secret' => $paymentIntent->client_secret]
        );
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

        if(filter_var($req->payment_method_on_file, FILTER_VALIDATE_BOOLEAN)) {
            $stripe = new Stripe\StripeClient(env('STRIPE_SECRET'));
            $paymentMethod = $account->findPaymentMethod($req->payment_method_id);
        }

        foreach($req->outstanding_invoices as $outstandingInvoice) {
            if($outstandingInvoice['payment_amount'] && $outstandingInvoice['payment_amount'] > 0 && $invoiceRepo->GetById($outstandingInvoice['invoice_id'])->balance_owing > 0) {
                $paymentIntent = null;

                if(filter_var($req->payment_method_on_file, FILTER_VALIDATE_BOOLEAN)) {
                    $paymentIntent = $stripe->paymentIntents->create([
                        'amount' => (float)$outstandingInvoice['payment_amount'] * 100,
                        'confirm' => true,
                        'currency' => env('CASHIER_CURRENCY'),
                        'customer' => $account->stripe_id,
                        'description' => 'Payment on FastForward Invoice #' . $outstandingInvoice['invoice_id'],
                        'payment_method' => $paymentMethod->id,
                    ]);
                }

                $payment = $paymentCollector->CollectAccountInvoicePayment($req, $outstandingInvoice, $paymentIntent);

                $paymentRepo->insert($payment);
                if($req->payment_type_id != $accountPaymentTypeId)
                    $accountAdjustment -= $outstandingInvoice['payment_amount'];
            }
        }

        if(number_format((float)$accountAdjustment, 2) != 0) {
            $accountRepo->AdjustBalance($req->account_id, $accountAdjustment);
            $comment = floatval($accountAdjustment > 0) ? 'Account credit applied' : 'Payment made from account balance';
            $paymentRepo->insert($paymentCollector->CollectAccountPayment($req, $accountAdjustment, $comment));
        }

        DB::commit();

        $newAccountBalance = $accountRepo->GetById($account->account_id)->account_balance;
        $newBalanceOwing = $invoiceRepo->CalculateAccountBalanceOwing($account->account_id);

        return response()->json([
            'account_balance' => $newAccountBalance,
            'balance_owing' => $newBalanceOwing,
            'success' => true
        ]);
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

    public function UndoPayment(Request $req) {
        if($req->user()->cannot('undo', Payment::class))
            abort(403);

        $paymentRepo = new Repos\PaymentRepo();
        $payment = $paymentRepo->GetById($req->payment_id);

        if($payment->invoice_id) {
            $invoiceRepo = new Repos\InvoiceRepo();
            $invoiceRepo->AdjustBalanceOwing($payment->invoice_id, $payment->amount);
        } else {
            $accountRepo = new Repos\AccountRepo();
            $accountRepo->AdjustBalance($payment->account_id, -($payment->amount));
        }
        $payment->delete();
    }
}
?>
