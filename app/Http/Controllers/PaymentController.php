<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Models;
use App\Http\Collectors;
use App\Http\Models\Payment\PaymentModelFactory;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\Payment;
use \App\Http\Validation\Utils;
use \Stripe;

class PaymentController extends Controller {
    public function deletePaymentMethod(Request $req, $accountId) {
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

    public function getAccountPaymentMethods(Request $req, $accountId) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($accountId);

        if($req->user()->cannot('updatePaymentMethods', $account))
            abort(403);

        $paymentModelFactory = new PaymentModelFactory();
        $paymentMethods = $paymentModelFactory->GetAccountStripePaymentMethods($account);

        return response()->json([
            'success' => true,
            'payment_methods' => $paymentMethods,
        ]);
    }

    public function getPaymentIntent(Request $req) {
        if($req->user()->cannot('create', Payment::class))
            abort(403);

        $paymentCollector = new Collectors\PaymentCollector();
        $paymentValidation = new \App\Http\Validation\PaymentValidationRules();

        $temp = $paymentValidation->GetPaymentIntentRules($req);
        $this->validate($req, $temp['rules'], $temp['messages']);

        $invoiceRepo = new Repos\InvoiceRepo();
        $paymentRepo = new Repos\PaymentRepo();

        $stripePaymentType = $paymentRepo->GetPaymentTypeByName('Stripe (Pending)');
        $invoice = Invoice::findOrFail($req->invoice_id);

        $pendingPaymentIntents = Payment::where('invoice_id', $invoice->invoice_id)
            ->where('payment_type_id', $stripePaymentType->payment_type_id)
            ->where('stripe_status', 'pending')
            ->get();

        if(!$pendingPaymentIntents->isEmpty())
            abort(422, 'Unable to request a new payment while an old one is pending. Please review or complete the pending payment before creating a new one.');

        $incompletePaymentIntents = Payment::where('invoice_id', $invoice->invoice_id)
                ->whereNotNull('stripe_payment_intent_id')
                ->where('payment_type_id', $stripePaymentType->payment_type_id)
                ->where('stripe_status', 'requires_payment_method')
                ->get();

        $stripe = new Stripe\StripeClient(config('services.stripe.secret'));

        $paymentAmount = (float)$req->amount * 100;
        // If there is an existing PaymentIntent that has not resolved, use that first so as to not create multiple database entries
        if(!$incompletePaymentIntents->isEmpty()) {
            $paymentIntent = $stripe->paymentIntents->retrieve($incompletePaymentIntents[0]['stripe_payment_intent_id']);
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
            // Cards only. API versions since 2023-08-16 otherwise turn on every payment method enabled in the
            // Dashboard, and the Payment Element would offer them - but payment recording assumes a card.
            'payment_method_types' => ['card'],
            'description' => 'Payment on FastForward Invoice #' . $invoice->invoice_id,
        ]);
        DB::beginTransaction();
        $paymentRepo->Insert($paymentCollector->CollectStripePaymentIntent($req, $invoice->invoice_id, $paymentIntent));
        DB::commit();

        return json_encode(
            ['client_secret' => $paymentIntent->client_secret]
        );
    }

    public function getReceivePaymentModel(Request $req, $invoiceId) {
        $invoiceRepo = new Repos\InvoiceRepo();
        $invoice = $invoiceRepo->GetById($invoiceId);

        if($req->user()->cannot('create', Payment::class))
            abort(403);

        $paymentModelFactory = new PaymentModelFactory();

        return json_encode($paymentModelFactory->GetReceivePaymentModel($invoice));
    }

    public function getSetupIntent(Request $req, $accountId) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($accountId);

        if($req->user()->cannot('updatePaymentMethods', $account))
            abort(403);

        $stripeId = $account->createOrGetStripeCustomer();
        $setupIntent = $account->createSetupIntent(['customer' => $stripeId, 'payment_method_types' => ['card']]);

        return response()->json([
            'success' => true,
            'client_secret' => $setupIntent->client_secret
        ]);
    }

    public function processPayment(Request $req, $invoiceId) {
        if($req->user()->cannot('create', Payment::class))
            abort(403);

        $invoiceRepo = new Repos\InvoiceRepo();
        $invoice = Invoice::FindOrFail($invoiceId);

        $paymentRepo = new Repos\PaymentRepo();
        $paymentMethod = $paymentRepo->GetPaymentType($req->payment_method['payment_type_id']);

        switch($req->payment_method['type'] ?? $paymentMethod->type) {
            case 'account':
                return $this->processPaymentFromAccount($req, $invoice);
            case 'card_on_file':
                return $this->processCardOnFilePayment($req, $invoice);
            case 'prepaid':
                return $this->processPrepaidPayment($req, $invoice);
            case 'employee':
            case 'stripe_pending':
                break;
        }
    }

    private function processPaymentFromAccount(Request $req, $invoice) {
        $accountRepo = new Repos\AccountRepo();
        $invoiceRepo = new Repos\InvoiceRepo();
        $paymentRepo = new Repos\PaymentRepo();

        $paymentCollector = new Collectors\PaymentCollector();
        $paymentValidation = new \App\Http\Validation\PaymentValidationRules();

        $temp = $paymentValidation->GetAccountCreditPaymentRules($req, $invoice);
        $this->validate($req, $temp['rules'], $temp['messages']);

        $payment = $paymentCollector->CollectPaymentFromAccount($req, $invoice);

        DB::beginTransaction();

        $payment = $paymentRepo->insert($payment);
        $accountRepo->AdjustBalance($invoice->account_id, -$payment->amount);
        $invoiceRepo->AdjustBalanceOwing($invoice->invoice_id, -$payment->amount);

        DB::commit();

        return response()->json(['success' => true]);
    }

    private function processCardOnFilePayment(Request $req, $invoice) {
        $accountRepo = new Repos\AccountRepo();
        $invoiceRepo = new Repos\InvoiceRepo();

        $paymentCollector = new Collectors\PaymentCollector();

        $stripe = new Stripe\StripeClient(config('services.stripe.secret'));
        $account = Account::FindOrFail($invoice->account_id);
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

    private function processPrepaidPayment(Request $req, $invoice) {
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

    public function setDefaultPaymentMethod(Request $req, $accountId) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($accountId);

        if($req->user()->cannot('updatePaymentMethods', $account))
            abort(403);

        $paymentMethods = $account->paymentMethods('card');
        $newDefault = $account->findPaymentMethod($req->payment_method_id);

        $account->updateDefaultPaymentMethod($req->payment_method_id);

        return response()->json([
            'success' => true
        ]);
    }

    public function revertPayment(Request $req, $paymentId) {
        $payment = Payment::findOrFail($paymentId);
        if(!$payment || $req->user()->cannot('revert', $payment))
            abort(403);

        $paymentRepo = new Repos\PaymentRepo();
        $accountPaymentTypeId = $paymentRepo->GetPaymentTypeByName('Account')->payment_type_id;

        DB::beginTransaction();

        if($payment->isStripeTransaction()) {
            $stripe = app(Stripe\StripeClient::class);

            $refundParams = [
                'payment_intent' => $payment->stripe_payment_intent_id,
                // Shown under Metadata on the refund in the Stripe Dashboard. Values are strings of at most 500 characters.
                'metadata' => [
                    'reason' => mb_substr((string)$req->reason, 0, 500),
                    'invoice_id' => (string)$payment->invoice_id,
                ],
            ];
            // Stripe's own `reason` only accepts these values and rejects the whole refund for anything else,
            // so free text from "Other" travels in the metadata above (and in our record's comment) only
            if(in_array($req->reason, ['duplicate', 'fraudulent', 'requested_by_customer']))
                $refundParams['reason'] = $req->reason;

            try {
                $refund = $stripe->refunds->create($refundParams);
            } catch (Stripe\Exception\ApiErrorException $e) {
                DB::rollBack();
                return response()->json(['errors' => [
                    'stripe_error' => [$e->getMessage()]
                ]], 400);
            }

            // The money has moved at Stripe from here on. A failure below must not be reported as "refund failed"
            // (it was once, for every refund: see the $request typo fixed here), so let it surface as an error.
            $refundPayment = $payment->replicate();
            $refundPayment['amount'] = -$payment->amount;
            $refundPayment['comment'] = $req->reason;
            $refundPayment['stripe_object_type'] = 'refund';
            $refundPayment['stripe_refund_id'] = $refund->id;
            $refundPayment['stripe_status'] = 'pending';
            $refundPayment->save();
        } else {
            $payment->update(['comment' => $req->reason]);

            if($payment->invoice_id) {
                $invoiceRepo = new Repos\InvoiceRepo();
                $invoiceRepo->AdjustBalanceOwing($payment->invoice_id, $payment->amount);
            }
            if($payment->payment_type_id == $accountPaymentTypeId) {
                $accountRepo = new Repos\AccountRepo();
                $accountRepo->AdjustBalance($payment->account_id, $payment->amount);
            }

            $payment->delete();
        }

        DB::commit();

        return response()->json([
            'success' => true,
        ]);
    }
}

?>
