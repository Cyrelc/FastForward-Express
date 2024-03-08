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

        $stripe = new Stripe\StripeClient(config('services.stripe.secret'));

        $paymentAmount = (float)$req->amount * 100;
        // If there is an existing PaymentIntent that has not resolved, use that first so as to not create multiple database entries
        if(!$incompletePaymentIntents->isEmpty()) {
            $paymentIntent = $stripe->paymentIntents->retrieve($incompletePaymentIntents[0]['payment_intent_id']);
            if($paymentAmount != $paymentIntent->amount) {
                $stripe->paymentIntents->update($paymentIntent->id, ['amount' => $paymentAmount]);
                $paymentRepo->Update($incompletePaymentIntents[0]->payment_id, [
                    'amount' => $req->amount
                ]);
            }

            return json_encode([
                'client_secret' => $paymentIntent->client_secret
            ]);
        }

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => $paymentAmount,
            'currency' => config('services.stripe.currency'),
            'description' => 'Payment on FastForward Invoice #' . $outstandingInvoice['invoice_id'],
        ]);
        DB::beginTransaction();
        $paymentRepo->Insert($paymentCollector->CollectStripePaymentIntent($req, $outstandingInvoice, $paymentIntent));
        DB::commit();

        return json_encode(
            ['client_secret' => $paymentIntent->client_secret]
        );
    }

    public function GetReceivePaymentModel(Request $req, $invoiceId) {
        $invoiceRepo = new Repos\InvoiceRepo();
        $invoice = $invoiceRepo->GetById($invoiceId);

        if($req->user()->cannot('create', Payment::class))
            abort(403);

        $paymentModelFactory = new Models\Payment\PaymentModelFactory();

        return json_encode($paymentModelFactory->GetReceivePaymentModel($invoice));
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

    public function ProcessPayment(Request $req, $invoiceId) {
        if($req->user()->cannot('create', Payment::class))
            abort(403);

        $invoiceRepo = new Repos\InvoiceRepo();
        $invoice = $invoiceRepo->GetById($invoiceId);
        if(!$invoice)
            abort(404, 'Invoice not found');

        $paymentRepo = new Repos\PaymentRepo();
        $paymentMethod = $paymentRepo->GetPaymentType($req->payment_method['payment_type_id']);

        switch($req->payment_method['type'] ?? $paymentMethod->type) {
            case 'account':
                return $this->ProcessPaymentFromAccount($req, $invoice);
            case 'card_on_file':
                return $this->ProcessCardOnFilePayment($req, $invoice);
            case 'prepaid':
                return $this->ProcessPrepaidPayment($req, $invoice);
            case 'employee':
            case 'stripe_pending':
                break;
        }
    }

    private function ProcessPaymentFromAccount(Request $req, $invoice) {
        $invoiceRepo = new Repos\InvoiceRepo();
        $paymentRepo = new Repos\PaymentRepo();

        $paymentCollector = new Collectors\PaymentCollector();
        $paymentValidation = new \App\Http\Validation\PaymentValidationRules();

        $temp = $paymentValidation->GetAccountCreditPaymentRules($req, $invoice);
        $this->validate($req, $temp['rules'], $temp['messages']);

        $payment = $paymentCollector->CollectPaymentFromAccount($req, $invoice);

        DB::beginTransaction();

        $payment = $paymentRepo->insert($payment);
        $invoiceRepo->AdjustBalanceOwing($invoice->invoice_id, -$payment->amount);

        DB::commit();

        return response()->json(['success' => true]);
    }

    private function ProcessCardOnFilePayment(Request $req, $invoice) {
        $accountRepo = new Repos\AccountRepo();
        $invoiceRepo = new Repos\InvoiceRepo();

        $paymentCollector = new Collectors\PaymentCollector();

        $stripe = new Stripe\StripeClient(config('services.stripe.secret'));
        $account = $accountRepo->GetById($invoice->account_id);
        $stripePaymentMethod = $account->findPaymentMethod($req->payment_method['payment_method_id']);

        $paymentRepo = new Repos\PaymentRepo();

        $paymentAmount = (float)$req->amount * 100;

        try {
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $paymentAmount,
                'automatic_payment_methods' => ['allow_redirects' => 'never', 'enabled' => true],
                'confirm' => true,
                'currency' => config('services.stripe.currency'),
                'customer' => $account->stripe_id,
                'description' => 'Payment on FastForward Invoice #' . $invoice->invoice_id,
                'payment_method' => $stripePaymentMethod->id,
            ]);
        } catch (Stripe\Exception\CardException $e) {
            $error = $e->getJsonBody()['error'];

            abort(400, $error['message']);
        }

        $payment = $paymentCollector->CollectCardOnFile($req, $invoice, $paymentIntent);

        DB::beginTransaction();
        $paymentRepo->insert($payment);
        DB::commit();

        return response()->json(['success' => true]);
    }

    private function ProcessPrepaidPayment(Request $req, $invoice) {
        $invoiceRepo = new Repos\InvoiceRepo();
        $paymentRepo = new Repos\PaymentRepo();

        $paymentCollector = new Collectors\PaymentCollector;
        $paymentValidation = new \App\Http\Validation\PaymentValidationRules();

        $temp = $paymentValidation->GetPrepaidRules($req, $invoice);
        $this->validate($req, $temp['rules'], $temp['messages']);

        $payment = $paymentCollector->CollectPrepaid($req, $invoice);

        DB::beginTransaction();

        $payment = $paymentRepo->insert($payment);
        $invoiceRepo->AdjustBalanceOwing($invoice->invoice_id, -$payment->amount);

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

    public function RevertPayment(Request $req, $paymentId) {
        if($req->user()->cannot('undo', Payment::class))
            abort(403);

        $paymentRepo = new Repos\PaymentRepo();
        $payment = $paymentRepo->GetById($paymentId);
        $accountPaymentTypeId = $paymentRepo->GetPaymentTypeByName('Account')->payment_type_id;

        DB::beginTransaction();

        if($payment->invoice_id && !$payment->isStripeTransaction()) {
            $invoiceRepo = new Repos\InvoiceRepo();
            $invoiceRepo->AdjustBalanceOwing($payment->invoice_id, $payment->amount);
        }
        if($payment->payment_type_id == $accountPaymentTypeId) {
            $accountRepo = new Repos\AccountRepo();
            $accountRepo->AdjustBalance($payment->account_id, $payment->amount);
        }

        $payment->delete();

        DB::commit();
    }
}
?>
