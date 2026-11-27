<?php

namespace Tests\Feature;

use App\Http\Repos\ChargebackRepo;
use App\Http\Repos\LineItemRepo;
use App\Models\Address;
use App\Models\Chargeback;
use App\Models\Employee;
use App\Models\LineItem;
use App\Models\Manifest;
use App\Models\User;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Employee charges settle by chargeback: generating a manifest creates a chargeback "schedule" for the bill and
 * links the line items to it; a later manifest deducts it, saving a copy that carries that manifest's id.
 */
class EmployeeChargebackTest extends TestCase {
    use RefreshDatabase;

    private $employee;

    protected function setUp(): void {
        parent::setUp();
        $this->employee = Employee::factory()->create();
    }

    private function makeEmployeeLineItem() {
        $billId = DB::table('bills')->insertGetId([
            'created_by' => User::factory()->create()->id,
            'delivery_address_id' => Address::factory()->create()->address_id,
            'pickup_address_id' => Address::factory()->create()->address_id,
            'description' => 'Delivery charged to an employee',
            'percentage_complete' => 100,
            'time_call_received' => '2026-11-10 09:00:00',
            'time_pickup_scheduled' => '2026-11-10 10:00:00',
            'time_delivery_scheduled' => '2026-11-10 12:00:00',
        ], 'bill_id');
        $chargeId = DB::table('charges')->insertGetId([
            'bill_id' => $billId,
            'charge_type_id' => DB::table('payment_types')->where('name', 'Employee')->value('payment_type_id')
                ?? DB::table('payment_types')->insertGetId(['name' => 'Employee', 'type' => 'employee'], 'payment_type_id'),
            'charge_employee_id' => $this->employee->employee_id,
        ], 'charge_id');

        return LineItem::create([
            'charge_id' => $chargeId, 'name' => 'Basic Weight Rate', 'type' => 'weightRate', 'price' => 18.90, 'driver_amount' => 0,
        ]);
    }

    private function billIdOf(LineItem $lineItem) {
        return DB::table('charges')->where('charge_id', $lineItem->charge_id)->value('bill_id');
    }

    private function makeSchedule(LineItem $lineItem, $countRemaining = 1) {
        // As ManifestRepo creates it when a manifest sweeps up the employee charge
        $chargeback = (new ChargebackRepo())->CreateBillChargeback([
            'bill_id' => $this->billIdOf($lineItem),
            'employee_id' => $this->employee->employee_id,
            'manifest_id' => null,
            'amount' => 18.90,
            'gl_code' => null,
            'name' => 'Chargeback for bill ' . $this->billIdOf($lineItem),
            'description' => '',
            'continuous' => false,
            'count_remaining' => $countRemaining,
            'start_date' => '2026-11-10',
        ]);
        $lineItem->update(['chargeback_id' => $chargeback->chargeback_id]);

        return $chargeback;
    }

    private function makeManifest() {
        return Manifest::create([
            'employee_id' => $this->employee->employee_id, 'date_run' => '2026-11-30',
            'start_date' => '2026-11-01', 'end_date' => '2026-11-30',
        ]);
    }

    private function lineItemAsShown(LineItem $lineItem) {
        return (new LineItemRepo())->GetByChargeId($lineItem->charge_id)->first();
    }

    public function test_bill_chargebacks_keep_their_bill() {
        // bill_id used to be dropped silently: it was in no migration and not fillable
        $lineItem = $this->makeEmployeeLineItem();

        $schedule = $this->makeSchedule($lineItem);

        $this->assertEquals($this->billIdOf($lineItem), $schedule->fresh()->bill_id);
    }

    public function test_a_charged_back_line_item_cannot_be_deleted() {
        $lineItem = $this->makeEmployeeLineItem();
        $this->makeSchedule($lineItem);

        try {
            (new LineItemRepo())->Delete($lineItem->line_item_id);
            $this->fail('Deleting a charged back line item should be refused');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertNotNull(LineItem::find($lineItem->line_item_id));
    }

    public function test_a_line_item_not_yet_charged_back_can_still_be_deleted() {
        $lineItem = $this->makeEmployeeLineItem();

        (new LineItemRepo())->Delete($lineItem->line_item_id);

        $this->assertNull(LineItem::find($lineItem->line_item_id));
    }

    public function test_the_bill_page_sees_a_pending_chargeback() {
        $lineItem = $this->makeEmployeeLineItem();
        $schedule = $this->makeSchedule($lineItem);

        $shown = $this->lineItemAsShown($lineItem);

        $this->assertEquals($schedule->chargeback_id, $shown->chargeback_id);
        $this->assertEquals(1, $shown->chargeback_count_remaining);
        $this->assertNull($shown->chargeback_manifest_ids);
    }

    public function test_the_bill_page_sees_the_manifest_that_deducted_it() {
        $lineItem = $this->makeEmployeeLineItem();
        $schedule = $this->makeSchedule($lineItem);
        $manifest = $this->makeManifest();

        (new ChargebackRepo())->RunChargebacksForManifest($manifest);

        $shown = $this->lineItemAsShown($lineItem);
        $this->assertEquals(0, $shown->chargeback_count_remaining);
        $this->assertEquals((string)$manifest->manifest_id, $shown->chargeback_manifest_ids);
        // The deduction copy now carries the bill, which is how the bill page finds it
        $this->assertEquals($this->billIdOf($lineItem), Chargeback::where('manifest_id', $manifest->manifest_id)->value('bill_id'));
    }

    public function test_every_deducting_manifest_is_listed() {
        // e.g. the legacy employee charges that were charged back on two manifests
        $lineItem = $this->makeEmployeeLineItem();
        $this->makeSchedule($lineItem, 0);
        $first = $this->makeManifest();
        $second = $this->makeManifest();
        foreach([$second, $first] as $manifest)
            Chargeback::create([
                'employee_id' => $this->employee->employee_id, 'amount' => 18.90, 'name' => 'Chargeback for bill',
                'manifest_id' => $manifest->manifest_id, 'bill_id' => $this->billIdOf($lineItem),
            ]);

        $this->assertSame(
            $first->manifest_id . ',' . $second->manifest_id,
            $this->lineItemAsShown($lineItem)->chargeback_manifest_ids
        );
    }
}
