<?php
namespace App\Http\Repos;

use DB;
use App\Models\Bill;
use App\Models\Chargeback;
use App\Models\DriverChargeback;
use App\Models\Employee;
use App\Models\LineItem;
use App\Models\Manifest;
use App\Http\Filters\DateBetween;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class ManifestRepo {
    public function Create($employeeIds, $startDate, $endDate) {
        $manifests = array();
        $chargebackRepo = new ChargebackRepo();

        foreach($employeeIds as $employeeId) {
            $manifest = $this->GenerateManifest($employeeId, $startDate, $endDate);
            $this->ManifestLineItems($manifest->manifest_id, $employeeId, $startDate, $endDate);
            $chargebackRepo->RunChargebacksForManifest($manifest);
            $manifests[] = $manifest;
        }

        return $manifests;
    }

    /**
     * Undoes a manifest: frees its line items to be manifested again, and hands each chargeback deduction it took
     * back to the chargeback it came from, so the next manifest takes it again. Run inside a transaction.
     */
    public function delete($manifestId) {
        $pickupLineItems = LineItem::where('pickup_manifest_id', $manifestId)->get();
        $deliveryLineItems = LineItem::where('delivery_manifest_id', $manifestId)->get();
        $deductions = Chargeback::where('manifest_id', $manifestId)->get();
        $manifest = Manifest::where('manifest_id', $manifestId);

        // Deductions taken before source_chargeback_id existed can't be traced to their chargeback, so deleting
        // the manifest would forgive them for good. Refuse rather than lose them quietly.
        $untraceable = $deductions->whereNull('source_chargeback_id');
        // Only a few manifests from mid-2023 and earlier, which the backfill migration couldn't match; fix by hand.
        if($untraceable->isNotEmpty())
            abort(409, 'Manifest ' . $manifestId . ' can\'t be deleted: chargeback deduction(s) #'
                . $untraceable->pluck('chargeback_id')->join(', #')
                . ' have no source chargeback recorded (source_chargeback_id), so deleting it would forgive them.');

        foreach($pickupLineItems as $lineItem) {
            $lineItem->pickup_manifest_id = null;

            $lineItem->save();
        }
        foreach($deliveryLineItems as $lineItem) {
            $lineItem->delivery_manifest_id = null;

            $lineItem->save();
        }
        foreach($deductions as $deduction) {
            // Continuous chargebacks have nothing to hand back. A source that has since been deleted has nothing
            // to receive it. Bill chargebacks stay linked to their line items, so they are deducted again rather
            // than swept up a second time.
            $source = Chargeback::lockForUpdate()->find($deduction->source_chargeback_id);
            if($source && !$source->continuous) {
                $source->count_remaining++;
                $source->save();
            }
            $deduction->delete();
        }
        $manifest->delete();
        return;
    }

    public function GenerateManifest($employeeId, $startDate, $endDate) {
        $manifest = [
            'employee_id' => $employeeId,
            'date_run' => date('Y-m-d'),
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $new = new Manifest();
        return $new->create($manifest);
    }

    public function GetMonthlyEmployeePay($date) {
        $income = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->whereNotNull('delivery_manifest_id')
            ->whereNotNull('pickup_manifest_id')
            ->leftjoin('manifests as pickup_manifest', 'pickup_manifest.manifest_id', '=', 'line_items.pickup_manifest_id')
            ->leftjoin('manifests as delivery_manifest', 'delivery_manifest.manifest_id', '=', 'line_items.delivery_manifest_id')
            ->whereDate('pickup_manifest.end_date', '>=', $date)
            ->whereDate('delivery_manifest.end_date', '>=', $date)
            ->select(
                DB::raw('sum(round(driver_amount * pickup_driver_commission, 2) + round(driver_amount * delivery_driver_commission, 2)) as employee_income'),
                DB::raw('date_format(pickup_manifest.end_date, "%Y-%m") as month')
            )->groupBy('month')
            ->orderBy('month');

        return $income->get();
    }

    public function GetById($manifest_id) {
        return Manifest::where('manifest_id', $manifest_id)->first();
    }

    public function ListAll($req, $employeeId = null) {
        $manifests = Manifest::leftJoin('employees', 'employees.employee_id', '=', 'manifests.employee_id')
            ->leftJoin('contacts', 'employees.contact_id', '=', 'contacts.contact_id')
            ->select(
                'manifest_id',
                'employees.employee_id',
                DB::raw('concat(first_name, " ", last_name) as employee_name'),
                // bill_count comes from GetListTotals - a per-manifest subquery here scanned every line item once per manifest
                DB::raw('date_format(date_run, "%Y") as year'),
                'date_run',
                'manifests.start_date',
                'end_date'
            );

        if($employeeId)
            $manifests->where('employees.employee_id', $employeeId);

        $filteredManifests = QueryBuilder::for($manifests)
            ->allowedFilters([
                AllowedFilter::exact('driver_id', 'manifests.employee_id'),
                AllowedFilter::custom('start_date', new DateBetween, 'manifests.start_date'),
                AllowedFilter::custom('end_date', new DateBetween)
            ]);

        return $filteredManifests->get();
    }

    /**
     * Bill count and driver gross for many manifests in one query, keyed by manifest_id - the same numbers as
     * BillRepo::CountByManifestId and LineItemRepo::GetDriverTotalByManifestId give for one.
     *
     * A line item can be on a manifest as the pickup, the delivery, or both. Matching
     * "pickup_manifest_id = X OR delivery_manifest_id = X" can't use either column's index, so each side is read
     * separately (UNION ALL keeps a line item that is both, so its two commissions both count) and then grouped.
     */
    public function GetListTotals($manifestIds) {
        $manifestIds = array_values(array_map('intval', $manifestIds));
        if(!$manifestIds)
            return collect();

        $placeholders = implode(',', array_fill(0, count($manifestIds), '?'));
        $side = fn($side) => "select line_items.{$side}_manifest_id as manifest_id, charges.bill_id,
                round(line_items.driver_amount * bills.{$side}_driver_commission, 2) as driver_amount
            from line_items
            join charges on charges.charge_id = line_items.charge_id
            left join bills on bills.bill_id = charges.bill_id
            where line_items.{$side}_manifest_id in ($placeholders)";

        return collect(DB::select(
            "select manifest_id, count(distinct bill_id) as bill_count, sum(driver_amount) as driver_gross
                from ({$side('pickup')} union all {$side('delivery')}) as manifest_line_items
                group by manifest_id",
            array_merge($manifestIds, $manifestIds)
        ))->keyBy('manifest_id');
    }

    public function Regather($manifestId) {
        $manifest = $this->GetById($manifestId);
        $this->ManifestLineItems(
            $manifest->manifest_id,
            $manifest->employee_id,
            $manifest->start_date,
            $manifest->end_date
        );
    }

    /**
     * Private functions
     */

    private function ManifestLineItems($manifestId, $driverId, $startDate, $endDate) {
        $chargebackRepo = new ChargebackRepo();

        $pickupLineItems = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->whereBetween(DB::raw('date(time_pickup_scheduled)'), [$startDate, $endDate])
            ->where('driver_amount', '!=', 0)
            ->where(DB::raw('coalesce(line_items.pickup_driver_id, bills.pickup_driver_id)'), $driverId)
            ->where('pickup_manifest_id', null)
            ->where('percentage_complete', 100)
            ->update(['pickup_manifest_id' => $manifestId, 'line_items.pickup_driver_id' => $driverId]);

        $deliveryLineItems = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->whereBetween(DB::raw('date(time_pickup_scheduled)'), [$startDate, $endDate])
            ->where('driver_amount', '!=', 0)
            ->where(DB::raw('coalesce(line_items.delivery_driver_id, bills.delivery_driver_id)'), $driverId)
            ->where('delivery_manifest_id', null)
            ->where('percentage_complete', 100)
            ->update(['delivery_manifest_id' => $manifestId, 'line_items.delivery_driver_id' => $driverId]);

        // Employee charges not yet charged back: one chargeback per bill, linked to exactly the line items it
        // covers. (Grouping in SQL linked only one charge's line items when a bill had several, so the rest were
        // charged back again on the next manifest.)
        $unchargedLineItems = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->whereBetween(DB::raw('date(time_pickup_scheduled)'), [$startDate, $endDate])
            ->where('charge_employee_id', $driverId)
            ->whereRaw('charge_type_id = (select payment_type_id from payment_types where name = "Employee")')
            ->where('price', '!=', 0)
            ->where('percentage_complete', 100)
            ->where('line_items.chargeback_id', null)
            ->select(
                'line_items.line_item_id',
                'line_items.price',
                'charges.bill_id as bill_id',
                DB::raw('date(time_pickup_scheduled) as date_pickup_scheduled')
            )->get();

        foreach($unchargedLineItems->groupBy('bill_id') as $billId => $lineItems) {
            $billChargeback = [
                'bill_id' => $billId,
                'employee_id' => $driverId,
                'manifest_id' => null,
                'amount' => round($lineItems->sum('price'), 2),
                'gl_code' => null,
                'name' => 'Chargeback for bill ' . $billId,
                'description' => '',
                'continuous' => false,
                'count_remaining' => 1,
                'start_date' => $lineItems->first()->date_pickup_scheduled
            ];

            $newChargeback = $chargebackRepo->CreateBillChargeback($billChargeback);

            LineItem::whereIn('line_item_id', $lineItems->pluck('line_item_id'))
                ->update(['chargeback_id' => $newChargeback->chargeback_id]);
        }
    }
}
