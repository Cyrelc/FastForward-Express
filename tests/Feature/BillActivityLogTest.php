<?php

namespace Tests\Feature;

use App\Http\Repos\ActivityLogRepo;
use App\Models\Address;
use App\Models\User;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A bill's activity log holds only that bill's entries. Since Laravel 11, orWhere([[...], [...]]) joins its own
 * conditions with OR, which made every address, charge and line item ever logged part of every bill's log - over
 * 180,000 rows, enough to exhaust memory on each bill load.
 */
class BillActivityLogTest extends TestCase {
    use RefreshDatabase;

    private function makeBill() {
        $pickup = Address::factory()->create()->address_id;
        $delivery = Address::factory()->create()->address_id;
        $billId = DB::table('bills')->insertGetId([
            'created_by' => User::factory()->create()->id,
            'pickup_address_id' => $pickup,
            'delivery_address_id' => $delivery,
            'description' => 'Test delivery',
            'percentage_complete' => 100,
            'time_call_received' => '2026-11-10 09:00:00',
            'time_pickup_scheduled' => '2026-11-10 10:00:00',
            'time_delivery_scheduled' => '2026-11-10 12:00:00',
        ], 'bill_id');
        $chargeId = DB::table('charges')->insertGetId([
            'bill_id' => $billId, 'charge_type_id' => DB::table('payment_types')->value('payment_type_id'),
        ], 'charge_id');

        return (object)compact('billId', 'pickup', 'delivery', 'chargeId');
    }

    private function log($subjectType, $subjectId, $properties, $logName = 'default') {
        return DB::table('activity_log')->insertGetId([
            'log_name' => $logName, 'description' => 'updated', 'subject_type' => $subjectType, 'subject_id' => $subjectId,
            'properties' => json_encode(['attributes' => $properties]), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_a_bills_activity_log_holds_only_its_own_entries() {
        $this->actingAs(User::factory()->create());
        $bill = $this->makeBill();
        $other = $this->makeBill();
        DB::table('activity_log')->delete(); // drop what creating the fixtures logged

        $expected = [
            $this->log('App\Models\Bill', $bill->billId, ['description' => 'Test delivery']),
            $this->log('App\Models\Address', $bill->pickup, ['name' => 'Pickup']),
            $this->log('App\Models\Address', $bill->delivery, ['name' => 'Delivery']),
            $this->log('App\Models\Charge', $bill->chargeId, ['bill_id' => $bill->billId, 'charge_type_id' => 1]),
            $this->log('App\Models\LineItem', 999001, ['charge_id' => $bill->chargeId, 'price' => '10.00']),
            $this->log('App\Models\LineItem', 999002, ['price' => '5.00', 'charge_id' => $bill->chargeId]), // ends with }
        ];

        // None of these belong to the bill
        $this->log('App\Models\Bill', $other->billId, ['description' => 'Other']);
        $this->log('App\Models\Address', $other->pickup, ['name' => 'Other pickup']);
        $this->log('App\Models\Charge', $other->chargeId, ['bill_id' => $other->billId]);
        $this->log('App\Models\LineItem', 999003, ['charge_id' => $other->chargeId]);
        // Same leading digits: bill 1 mustn't match bill 12, nor charge 1 charge 12
        $this->log('App\Models\Charge', 999004, ['bill_id' => (int)($bill->billId . '2')]);
        $this->log('App\Models\LineItem', 999005, ['charge_id' => (int)($bill->chargeId . '2')]);
        // Debug noise about the bill's own address
        $this->log('App\Models\Address', $bill->pickup, ['name' => 'debug'], 'system_debug');

        $entry = fn($row) => $row->subject_type . '#' . $row->subject_id . ' ' . $row->properties;
        $log = (new ActivityLogRepo())->GetBillActivityLog($bill->billId);

        $this->assertEqualsCanonicalizing(
            DB::table('activity_log')->whereIn('id', $expected)->get()->map($entry)->all(),
            $log->map($entry)->all()
        );
    }
}
