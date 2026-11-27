<?php

namespace Tests\Feature;

use App\Http\Repos\InvoiceRepo;
use App\Http\Repos\LineItemRepo;
use App\Models\Address;
use App\Models\Invoice;
use App\Models\LineItem;
use App\Models\User;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Whether an invoice is paid lives only in its balance_owing. Line items used to carry a `paid` flag too: it was
 * set (on the last line item only) when an invoice reached 0, never cleared by a revert or refund, and its
 * column was dropped from the schema in 2023 - so paying an invoice in full threw on any newer database.
 */
class InvoiceBalanceTest extends TestCase {
    use RefreshDatabase;

    private function makeInvoice($balanceOwing) {
        return DB::table('invoices')->insertGetId([
            'bill_start_date' => '2026-11-01', 'bill_end_date' => '2026-11-30', 'date' => '2026-11-30',
            'balance_owing' => $balanceOwing, 'bill_cost' => $balanceOwing, 'tax' => 0, 'total_cost' => $balanceOwing,
        ], 'invoice_id');
    }

    private function makeLineItem($invoiceId) {
        $billId = DB::table('bills')->insertGetId([
            'created_by' => User::factory()->create()->id,
            'delivery_address_id' => Address::factory()->create()->address_id,
            'pickup_address_id' => Address::factory()->create()->address_id,
            'description' => 'Test delivery',
            'percentage_complete' => 100,
            'time_call_received' => '2026-11-10 09:00:00',
            'time_pickup_scheduled' => '2026-11-10 10:00:00',
            'time_delivery_scheduled' => '2026-11-10 12:00:00',
        ], 'bill_id');
        $chargeId = DB::table('charges')->insertGetId([
            'bill_id' => $billId,
            'charge_type_id' => DB::table('payment_types')->value('payment_type_id'),
        ], 'charge_id');

        return LineItem::create([
            'charge_id' => $chargeId, 'invoice_id' => $invoiceId, 'name' => 'Delivery', 'type' => 'distanceRate',
            'price' => 40, 'driver_amount' => 40,
        ]);
    }

    public function test_paying_in_full_succeeds_without_line_items() {
        // This used to throw "Undefined variable $lineItem"
        $invoiceId = $this->makeInvoice(40);

        (new InvoiceRepo())->AdjustBalanceOwing($invoiceId, -40);

        $this->assertEquals(0, Invoice::find($invoiceId)->balance_owing);
    }

    public function test_paying_in_full_succeeds_with_line_items() {
        // This used to fail writing the dropped `paid` column
        $invoiceId = $this->makeInvoice(40);
        $this->makeLineItem($invoiceId);

        (new InvoiceRepo())->AdjustBalanceOwing($invoiceId, -40);

        $this->assertEquals(0, Invoice::find($invoiceId)->balance_owing);
    }

    public function test_a_refund_after_paying_in_full_reopens_the_invoice() {
        $invoiceId = $this->makeInvoice(40);
        $this->makeLineItem($invoiceId);
        $invoiceRepo = new InvoiceRepo();

        $invoiceRepo->AdjustBalanceOwing($invoiceId, -40); // paid
        $invoiceRepo->AdjustBalanceOwing($invoiceId, 40);  // refunded or reverted

        $this->assertEquals(40, Invoice::find($invoiceId)->balance_owing);
    }

    public function test_invoiced_line_items_still_cannot_be_deleted() {
        // The delete guard used to check `paid` as well, but only invoiced line items were ever marked paid
        $lineItem = $this->makeLineItem($this->makeInvoice(40));

        try {
            (new LineItemRepo())->Delete($lineItem->line_item_id);
            $this->fail('Deleting an invoiced line item should be refused');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertNotNull(LineItem::find($lineItem->line_item_id));
    }
}
