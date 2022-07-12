<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Models;
use App\Http\Collectors;
use App\Http\Models\Payment;
use App\Http\mpgClasses;
use \App\Http\Validation\Utils;


class PaymentController extends Controller {
    public function DeleteCreditCard(Request $req, $creditCardId) {
        $accountRepo = new Repos\AccountRepo();
        $paymentRepo = new Repos\PaymentRepo();
        $creditCard = $paymentRepo->GetCreditCardById($creditCardId);
        $account = $accountRepo->GetById($creditCard->account_id);

        if($req->user()->cannot('updatePaymentMethods', $account))
            abort(403);

        $transactionArray = array('type' => 'res_delete', 'data_key' => $creditCard->data_key);
        $mpgTransaction = new mpgClasses\mpgTransaction($transactionArray);

        $mpgRequest = new mpgClasses\mpgRequest($mpgTransaction);
        $mpgRequest->setTestMode(env('MONERIS_TEST_MODE'));

        $mpgHttpPost = new mpgClasses\mpgHttpsPost(env('MONERIS_STORE_ID'), env('MONERIS_API_TOKEN'), $mpgRequest);

        $mpgResponse = $mpgHttpPost->getMpgResponse();

        if($mpgResponse->getResSuccess() == true) {
            $paymentRepo->deleteCreditCard($creditCardId);

            return response()->json(['success' => true]);
        }
    }

    public function GetCreditCardsForAccount(Request $req, $accountId) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($accountId);

        if($req->user()->cannot('updatePaymentMethods', $account))
            abort(403);

        $paymentRepo = new Repos\PaymentRepo();
        $accountCards = $paymentRepo->GetCreditCardsByAccountId($account->account_id);

        $creditCards = [];

        foreach($accountCards as $creditCard) {
            $transactionArray = array('type' => 'res_lookup_masked', 'data_key' => $creditCard->data_key);
            $mpgTransaction = new mpgClasses\mpgTransaction($transactionArray);

            $mpgRequest = new mpgClasses\mpgRequest($mpgTransaction);
            $mpgRequest->setTestMode(env('MONERIS_TEST_MODE'));

            $mpgHttpPost = new mpgClasses\mpgHttpsPost(env('MONERIS_STORE_ID'), env('MONERIS_API_TOKEN'), $mpgRequest);

            $mpgResponse = $mpgHttpPost->getMpgResponse();

            if($mpgResponse->getResSuccess() == true) {
                $creditCards[] = [
                    'masked_pan' => $mpgResponse->getResDataMaskedPan(),
                    'expiry_date' => \DateTime::createFromFormat('ym', $mpgResponse->getResDataExpDate()),
                    'credit_card_id' => $creditCard->credit_card_id,
                    'payment_type_id' => $creditCard->payment_type_id,
                    'payment_type_name' => $creditCard->name
                ];
            }
        }

        return json_encode($creditCards);
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

        $creditCardsOnFile = json_decode($this->GetCreditCardsForAccount($req, $accountId));

        return json_encode($paymentModelFactory->GetReceivePaymentModel($accountId, $creditCardsOnFile));
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

        if(floatval($accountAdjustment) != 0) {
            $accountRepo->AdjustBalance($req->account_id, $accountAdjustment);
            $paymentRepo->insert($paymentCollector->CollectAccountPayment($req, $accountAdjustment));
        }

        if($req->credit_card_id) {
            $creditCard = $paymentRepo->GetCreditCardById($req->credit_card_id);

            $orderId = 'ord_' . date_create('now')->format('Y-m-d_H:i:s');
            $orderId .= substr($orderIdentifier, 0, 50 - strlen($orderId));

            $transactionArray = array (
                'type' => 'res_purchase_cc',
                'data_key' => $creditCard->data_key,
                'order_id' => $orderId,
                'amount' => $req->payment_amount,
                'crypt_type' => env('MONERIS_CRYPT_TYPE'),
                'cust_id' => $req->account_id
            );

            $mpgTransaction = new mpgClasses\mpgTransaction($transactionArray);
            $mpgRequest = new mpgClasses\mpgRequest($mpgTransaction);
            $mpgRequest->setProcCountryCode('CA');
            $mpgRequest->setTestMode(env('MONERIS_TEST_MODE'));

            $mpgHttpPost = new mpgClasses\mpgHttpsPost(env('MONERIS_STORE_ID'), env('MONERIS_API_TOKEN'), $mpgRequest);

            $mpgResponse = $mpgHttpPost->getMpgResponse();

            if($mpgResponse->getResSuccess() == true) {
                foreach($req->outstanding_invoices as $invoice) {
                    if($invoice['payment_amount'] > 0) {
                        $paymentRepo->LogMonerisTransaction([
                            'account_id' => $req->account_id,
                            'credit_card_id' => $req->credit_card_id,
                            'invoice_id' => $invoice['invoice_id'],
                            'order_id' => $orderId,
                            'type' => 'res_purchase_cc',
                            'user_id' => $req->user()->user_id
                        ]);
                    }
                }
            }
        }

        DB::commit();
        return response()->json(['success' => true]);
    }

    public function StoreCreditCard(Request $req) {
        $accountRepo = new Repos\AccountRepo();
        $account = $accountRepo->GetById($req->account_id);

        if($req->user()->cannot('updatePaymentMethods', $account))
            abort(403);

        $paymentRepo = new Repos\PaymentRepo();
        $paymentTypeId = null;
        switch($req->pan[0]) {
            case '3':
                $paymentTypeId = $paymentRepo->GetPaymentTypeByName('American Express');
            case '4':
                $paymentTypeId = $paymentRepo->GetPaymentTypeByName('Visa');
            case '5':
                $paymentTypeId = $paymentRepo->GetPaymentTypeByName('Mastercard');
        }

        if(!$paymentTypeId)
            abort(400, 'Invalid credit card type provided, we are currently only able to support Visa and Mastercard');

        $transactionType = 'res_add_cc';

        $transactionArray = array(
            'type' => $transactionType,
            'cust_id' => $account->account_id,
            'pan' => $req->pan,
            'expdate' => (new \Datetime($req->expiry_date))->format('ym'),
            'crypt_type' => env('MONERIS_CRYPT_TYPE'),
            'data_key_format' => '0U'
        );

        $avsTemplate = array(
            'avs_street_number' => $req->street_number,
            'avs_street_name' => $req->street,
            'avs_zipcode' => $req->zipPostal
        );

        $mpgAvsInfo = new mpgClasses\mpgAvsInfo($avsTemplate);

        $credentialsOnFile = new mpgClasses\CofInfo();
        $credentialsOnFile->setIssuerId('139X3130ASCXAS9');

        $mpgTransaction = new mpgClasses\mpgTransaction($transactionArray);
        $mpgTransaction->setAvsInfo($mpgAvsInfo);
        $mpgTransaction->setCofInfo($credentialsOnFile);

        $mpgRequest = new mpgClasses\mpgRequest($mpgTransaction);
        $mpgRequest->setTestMode(env('MONERIS_TEST_MODE'));

        $mpgHttpPost = new mpgClasses\mpgHttpsPost(env('MONERIS_STORE_ID'), env('MONERIS_API_TOKEN'), $mpgRequest);

        $mpgResponse = $mpgHttpPost->getMpgResponse();

        if($mpgResponse->getResSuccess() == true) {
            $paymentRepo = new Repos\PaymentRepo();
            $paymentRepo->InsertCreditCardForAccount($account->account_id, $mpgResponse->getDataKey(), $paymentTypeId);
        }

        return response()->json(['success' => true]);
    }
}
?>
