<?php

namespace Tests\Feature;

use App\Http\Repos\LineItemRepo;
use App\Http\Repos\ManifestRepo;
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
 * Every chargeback must be deducted exactly once: running, regathering and deleting manifests may neither
 * repeat a deduction nor lose one.
 */
class ManifestChargebackTest extends TestCase {
    use RefreshDatabase;

    private const START = '2026-11-01';
    private const END = '2026-11-30';

    private $employee;
    private $employeeChargeTypeId;

    protected function setUp(): void {
        parent::setUp();
        $this->employee = Employee::factory()->create();
        $this->employeeChargeTypeId = DB::table('payment_types')->where('name', 'Employee')->value('payment_type_id')
            ?? DB::table('payment_types')->insertGetId(['name' => 'Employee', 'type' => 'employee'], 'payment_type_id');
    }

    private function makeBill($attributes = []) {
        return DB::table('bills')->insertGetId($attributes + [
            'created_by' => User::factory()->create()->id,
            'delivery_address_id' => Address::factory()->create()->address_id,
            'pickup_address_id' => Address::factory()->create()->address_id,
            'description' => 'Test delivery',
            'percentage_complete' => 100,
            'time_call_received' => '2026-11-10 09:00:00',
            'time_pickup_scheduled' => '2026-11-10 10:00:00',
            'time_delivery_scheduled' => '2026-11-10 12:00:00',
        ], 'bill_id');
    }

    private function chargeToEmployee($billId, array $prices, $employee = null) {
        $chargeId = DB::table('charges')->insertGetId([
            'bill_id' => $billId, 'charge_type_id' => $this->employeeChargeTypeId,
            'charge_employee_id' => ($employee ?? $this->employee)->employee_id,
        ], 'charge_id');

        return collect($prices)->map(fn($price) => LineItem::create([
            'charge_id' => $chargeId, 'name' => 'Basic Weight Rate', 'type' => 'weightRate', 'price' => $price, 'driver_amount' => 0,
        ]));
    }

    private function runManifest() {
        return (new ManifestRepo())->Create([$this->employee->employee_id], self::START, self::END)[0];
    }

    private function deleteManifest($manifest) {
        DB::transaction(fn() => (new ManifestRepo())->delete($manifest->manifest_id));
    }

    private function deductions() {
        return Chargeback::where('employee_id', $this->employee->employee_id)->whereNotNull('manifest_id')->get();
    }

    public function test_an_employee_charge_is_charged_back_once() {
        $lineItems = $this->chargeToEmployee($this->makeBill(), [18.90, 5.10]);

        $manifest = $this->runManifest();
        $this->runManifest(); // e.g. a second run over the same dates
        (new ManifestRepo())->Regather($manifest->manifest_id);

        $this->assertCount(1, $this->deductions());
        $this->assertEquals(24.00, $this->deductions()->first()->amount);
        $schedule = Chargeback::find($this->deductions()->first()->source_chargeback_id);
        $this->assertSame(0, (int)$schedule->count_remaining);
        $this->assertNotNull($schedule->created_at);
        $this->assertNotNull($this->deductions()->first()->created_at);
        foreach($lineItems as $lineItem)
            $this->assertEquals($schedule->chargeback_id, $lineItem->fresh()->chargeback_id);
    }

    public function test_a_bill_with_two_employee_charges_is_charged_back_once() {
        // Grouping by bill used to link only one charge's line items, so the other was charged back again
        $billId = $this->makeBill();
        $first = $this->chargeToEmployee($billId, [10]);
        $second = $this->chargeToEmployee($billId, [7]);

        $this->runManifest();
        $this->runManifest();

        $this->assertCount(1, $this->deductions());
        $this->assertEquals(17, $this->deductions()->first()->amount);
        $this->assertNotNull($first->first()->fresh()->chargeback_id);
        $this->assertEquals($first->first()->fresh()->chargeback_id, $second->first()->fresh()->chargeback_id);
    }

    public function test_one_bill_can_be_charged_back_to_two_employees_each_for_their_part() {
        // e.g. driver A picked up and handed off to driver B, and both share the cost of an overcharge
        $driverB = Employee::factory()->create();
        $billId = $this->makeBill();
        $partA = $this->chargeToEmployee($billId, [12.50]);
        $partB = $this->chargeToEmployee($billId, [7.25], $driverB);

        [$manifestA, $manifestB] = (new ManifestRepo())->Create([$this->employee->employee_id, $driverB->employee_id], self::START, self::END);
        (new ManifestRepo())->Create([$this->employee->employee_id, $driverB->employee_id], self::START, self::END);

        $deductionA = Chargeback::where('employee_id', $this->employee->employee_id)->whereNotNull('manifest_id')->sole();
        $deductionB = Chargeback::where('employee_id', $driverB->employee_id)->whereNotNull('manifest_id')->sole();
        $this->assertEquals(12.50, $deductionA->amount);
        $this->assertEquals(7.25, $deductionB->amount);
        $this->assertNotEquals($partA->first()->fresh()->chargeback_id, $partB->first()->fresh()->chargeback_id);

        // Each driver's charge on the bill page names only their own manifest
        $shown = fn($lineItem) => (new LineItemRepo())->GetByChargeId($lineItem->charge_id)->first()->chargeback_manifest_ids;
        $this->assertSame((string)$deductionA->manifest_id, $shown($partA->first()));
        $this->assertSame((string)$deductionB->manifest_id, $shown($partB->first()));
    }

    public function test_deleting_a_manifest_hands_the_deduction_back_to_be_taken_once_more() {
        $lineItems = $this->chargeToEmployee($this->makeBill(), [18.90]);
        $manifest = $this->runManifest();
        $scheduleId = $lineItems->first()->fresh()->chargeback_id;

        $this->deleteManifest($manifest);

        $this->assertNull(Manifest::find($manifest->manifest_id));
        $this->assertCount(0, $this->deductions());
        $this->assertSame(1, (int)Chargeback::find($scheduleId)->count_remaining);
        $this->assertEquals($scheduleId, $lineItems->first()->fresh()->chargeback_id); // not swept up as new

        $this->runManifest();

        $this->assertCount(1, $this->deductions());
        $this->assertSame(0, (int)Chargeback::find($scheduleId)->count_remaining);
        // ...from the same chargeback, not a second one for the bill
        $billId = DB::table('charges')->where('charge_id', $lineItems->first()->charge_id)->value('bill_id');
        $this->assertSame(1, Chargeback::where('bill_id', $billId)->whereNull('manifest_id')->count());
    }

    public function test_deleting_a_manifest_restores_a_limited_chargebacks_instalment() {
        $limited = Chargeback::create([
            'employee_id' => $this->employee->employee_id, 'amount' => 50, 'name' => 'Uniform',
            'continuous' => false, 'count_remaining' => 3, 'start_date' => '2026-11-01',
        ]);

        $manifest = $this->runManifest();
        $this->assertSame(2, (int)$limited->fresh()->count_remaining);

        $this->deleteManifest($manifest);

        $this->assertSame(3, (int)$limited->fresh()->count_remaining);
        $this->assertCount(0, $this->deductions());
    }

    public function test_deleting_a_manifest_leaves_a_continuous_chargeback_alone() {
        $continuous = Chargeback::create([
            'employee_id' => $this->employee->employee_id, 'amount' => 20, 'name' => 'Phone',
            'continuous' => true, 'count_remaining' => 0, 'start_date' => '2026-11-01',
        ]);

        $this->deleteManifest($this->runManifest());

        $this->assertTrue((bool)$continuous->fresh()->continuous);
        $this->assertSame(0, (int)$continuous->fresh()->count_remaining);
        $this->assertCount(0, $this->deductions());
    }

    public function test_deleting_a_manifest_frees_its_line_items() {
        $billId = $this->makeBill(['pickup_driver_id' => $this->employee->employee_id, 'delivery_driver_id' => $this->employee->employee_id]);
        $chargeId = DB::table('charges')->insertGetId(['bill_id' => $billId, 'charge_type_id' => $this->employeeChargeTypeId], 'charge_id');
        $lineItem = LineItem::create(['charge_id' => $chargeId, 'name' => 'Delivery', 'type' => 'distanceRate', 'price' => 30, 'driver_amount' => 12]);

        $manifest = $this->runManifest();
        $this->assertEquals($manifest->manifest_id, $lineItem->fresh()->pickup_manifest_id);

        $this->deleteManifest($manifest);

        $this->assertNull($lineItem->fresh()->pickup_manifest_id);
        $this->assertNull($lineItem->fresh()->delivery_manifest_id);
    }

    public function test_a_manifest_with_untraceable_deductions_is_not_deleted() {
        // Deductions saved before source_chargeback_id existed: deleting would forgive them, so it is refused
        $manifest = $this->runManifest();
        $legacy = Chargeback::create([
            'employee_id' => $this->employee->employee_id, 'amount' => 18.90, 'name' => 'Chargeback for bill 18767',
            'manifest_id' => $manifest->manifest_id,
        ]);

        try {
            $this->deleteManifest($manifest);
            $this->fail('Deleting a manifest with untraceable deductions should be refused');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
            $this->assertStringContainsString('#' . $legacy->chargeback_id, $e->getMessage());
        }

        $this->assertNotNull(Manifest::find($manifest->manifest_id));
        $this->assertNotNull(Chargeback::find($legacy->chargeback_id));
    }
}
