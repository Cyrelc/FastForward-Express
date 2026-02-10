<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

use App\Jobs\ReceiveStripeWebhook;
use App\Jobs\ReceiveStripeRefundWebhook;

class WebhookController extends Controller {
    public function receivePaymentIntentUpdate(Request $req) {
        try {
            $event = \Stripe\Webhook::constructEvent($req->getContent(), $req->header('Stripe-Signature'), config('services.stripe.stripe_payment_intent_secret'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Webhook signature verification failed'], 403);
        }
        // If the event is valid, dispatch it to a job for further processing
        ReceiveStripeWebhook::dispatch($event);

        //Acknowledge that the request was received and successfully queued
        return response()->json(['success' => 'Webhook received and queued'], 200);
    }

    public function receiveRefundUpdate(Request $req) {
        try {
            $event = \Stripe\Webhook::constructEvent($req->getContent(), $req->header('Stripe-Signature'), config('services.stripe.stripe_refund_secret'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Webhook signature verification failed', 403]);
        }

        ReceiveStripeRefundWebhook::dispatch($event);

        return response()->json(['success' => 'Webhook received and queued'], 200);
    }

    public function storeSupervisorStatus(Request $request) {
        $payload = $request->all();

        $statusKey = "supervisor:process:{$payload['group_name']}:{$payload['process_name']}";

        $downEvents = ['PROCESS_STATE_BACKOFF','PROCESS_STATE_FATAL','PROCESS_STATE_EXITED','PROCESS_STATE_STOPPED'];
        $upEvents   = ['PROCESS_STATE_RUNNING'];

        $expected = $payload['expected'] ??null;
        $event = $payload['event'];

        $current = Cache::get($statusKey, null);

        if(in_array($event, $upEvents, true)) {
            $state = [
                'status' => 'UP',
                'last_change' => $payload['when'],
                'from_state' => $payload['from_state'] ?? null,
                'pid' => $payload['pid'] ?? null,
                'tries' => $payload['tries'] ?? null,
                'reason' => $event
            ];
        } elseif (in_array($event, $downEvents, true) && ($expected !== true)) {
            $state = [
                'last_change' => $payload['when'],
                'from_state' => $payload['from_state'] ?? null,
                'pid' => $payload['pid'] ?? null,
                'tries' => $payload['tries'] ?? null,
                'reason' => $event
            ];
        } else {
            $state = $current ?? [
                'status' => 'UNKNOWN',
                'last_change' => $payload['when']
            ];
        }

        if($state['status'] === 'UP') {
            $state['recovery_at'] = $payload['when'] + 15;
        }

        Cache::put($statusKey, $state, now()->addHours(6));

        $required = config('services.supervisor.required', []);
        $all = [];
        $allOk = true;
        foreach($required as $worker) {
            $all[$worker] = Cache::get('supervisor:process:' . $worker);
            $ok = $all[$worker] && $all[$worker]['status'] === 'UP' && (time() >= ($all[$worker]['recovery_at'] ?? 0));

            $allOk = $allOk && $ok;
        }

        return response()->json(['success' => true, 'status_stored' => true], 200);
    }
}

?>
