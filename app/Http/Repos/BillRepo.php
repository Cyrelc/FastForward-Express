<?php
namespace App\Http\Repos;

use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Filters\DateBetween;
use App\Http\Filters\NumberBetween;
use App\Http\Filters\BillFilters\Dispatch;
use App\Http\Filters\IsNull;

use App\Bill;
use DB;

class BillRepo {
	public function ListAll($req) {
        $bills = Bill::leftJoin('accounts', 'accounts.account_id', '=', 'bills.charge_account_id')
                ->leftJoin('addresses as pickup_address', 'pickup_address.address_id', '=', 'bills.pickup_address_id')
                ->leftJoin('addresses as delivery_address', 'delivery_address.address_id', '=', 'bills.delivery_address_id')
                ->leftJoin('interliners', 'interliners.interliner_id', '=', 'bills.interliner_id')
                ->leftJoin('employees as pickup_employee', 'pickup_employee.employee_id', '=', 'bills.pickup_driver_id')
                ->leftJoin('employees as delivery_employee', 'delivery_employee.employee_id', '=', 'bills.pickup_driver_id')
                ->leftJoin('contacts as pickup_employee_contact', 'pickup_employee.contact_id', '=', 'pickup_employee_contact.contact_id')
                ->leftJoin('contacts as delivery_employee_contact', 'delivery_employee.contact_id', '=', 'delivery_employee_contact.contact_id')
                ->leftJoin('payment_types', 'bills.payment_type_id', '=', 'payment_types.payment_type_id')
                ->leftJoin('accounts as parent_accounts', 'accounts.parent_account_id', '=', 'parent_accounts.account_id')
                ->leftJoin('selections', 'selections.value', '=', 'bills.delivery_type')
                ->select(
                    DB::raw('format(amount, 2) as amount'),
                    'bill_id',
                    'bill_number',
                    'charge_account_id',
                    'accounts.name as charge_account_name',
                    'accounts.account_number as charge_account_number',
                    'accounts.invoice_interval as invoice_interval',
                    'delivery_address.formatted as delivery_address_formatted',
                    'delivery_address.lat as delivery_address_lat',
                    'delivery_address.lng as delivery_address_lng',
                    'delivery_address.name as delivery_address_name',
                    'delivery_driver_id',
                    DB::raw('concat(delivery_employee_contact.first_name, " ", delivery_employee_contact.last_name) as delivery_employee_name'),
                    'delivery_manifest_id',
                    'selections.name as delivery_type',
                    'description',
                    DB::raw('coalesce(invoice_id, pickup_manifest_id, delivery_manifest_id) is null as editable'),
                    'interliner_cost',
                    'interliner_cost_to_customer',
                    'interliners.interliner_id',
                    'interliners.name as interliner_name',
                    'invoice_id',
                    'bills.payment_type_id',
                    'payment_types.name as payment_type',
                    DB::raw('case when accounts.can_be_parent = 1 then concat(accounts.account_id, " - ", accounts.name) when accounts.can_be_parent = 0 then concat(accounts.parent_account_id, " - ", parent_accounts.name) end as parent_account'),
                    'percentage_complete',
                    'pickup_address.formatted as pickup_address_formatted',
                    'pickup_address.lat as pickup_address_lat',
                    'pickup_address.lng as pickup_address_lng',
                    'pickup_address.name as pickup_address_name',
                    'pickup_driver_id',
                    DB::raw('concat(pickup_employee_contact.first_name, " ", pickup_employee_contact.last_name) as pickup_employee_name'),
                    'pickup_manifest_id',
                    'time_pickup_scheduled',
                    'time_delivery_scheduled',
                    'time_picked_up',
                    'time_delivered'
                );

            $filteredBills = QueryBuilder::for($bills)
                ->allowedFilters([
                    AllowedFilter::custom('amount', new NumberBetween),
                    AllowedFilter::exact('charge_account_id'),
                    AllowedFilter::custom('dispatch', new Dispatch),
                    AllowedFilter::exact('delivery_driver_id'),
                    AllowedFilter::exact('interliner_id', 'bills.interliner_id'),
                    AllowedFilter::exact('invoice_id'),
                    AllowedFilter::custom('invoiced', new IsNull, 'invoice_id'),
                    AllowedFilter::exact('invoice_interval'),
                    AllowedFilter::exact('skip_invoicing'),
                    AllowedFilter::custom('time_pickup_scheduled', new DateBetween),
                    AllowedFilter::custom('time_delivery_scheduled', new DateBetween),
                    AllowedFilter::exact('payment_type_id', 'bills.payment_type_id'),
                    AllowedFilter::custom('percentage_complete', new NumberBetween),
                    AllowedFilter::exact('pickup_driver_id')
                ]);

            return $filteredBills->get();
	}

    public function AssignToDriver($bill_id, $driver) {
        $old = $this->GetById($bill_id);

        $old->pickup_driver_id = $driver->driver_id;
        $old->delivery_driver_id = $driver->driver_id;
        $old->pickup_driver_commission = $driver->pickup_commission / 100;
        $old->delivery_driver_commission = $driver->delivery_commission / 100;
        $old->time_dispatched = new \DateTime();

        $old = $this->CheckRequiredFields($old);

        $old->save();

        return $old;
    }

    public function GetById($id) {
	    $bill = Bill::where('bill_id', '=', $id)->first();

	    return $bill;
    }

    public function GetByInvoiceId($id) {
        $invoiceRepo = new InvoiceRepo();
        $accountRepo = new AccountRepo();

        $account_id = $invoiceRepo->GetById($id)->account_id;

        $sort_options = $invoiceRepo->GetSortOrderById($account_id);
        $subtotal_by = $invoiceRepo->GetSubtotalById($account_id);

        $bill_query = Bill::where('invoice_id', $id)
                ->join('addresses as pickup', 'pickup.address_id', '=', 'bills.pickup_address_id')
                ->join('addresses as delivery', 'delivery.address_id', '=', 'bills.delivery_address_id')
                ->join('accounts', 'accounts.account_id', '=', 'bills.charge_account_id')
                ->select('bill_id',
                DB::raw('format(amount + case when interliner_cost_to_customer is not null then interliner_cost_to_customer else 0 end, 2) as amount'),
                'bill_number',
                'time_pickup_scheduled',
                'charge_account_id',
                'pickup_account_id',
                'delivery_account_id',
                'charge_reference_value',
                'delivery_type',
                'pickup.name as pickup_address_name',
                'delivery.name as delivery_address_name',
                'accounts.name as charge_account_name');

        $bills = array();
        if($subtotal_by == NULL) {
            foreach($sort_options as $option) {
                $bill_query->orderBy($option->database_field_name);
            }

            $bills[0] = new \stdClass();
            $bills[0]->bills = $bill_query->get();
        } else {
            $subtotal_ids = Bill::where('invoice_id', $id)->groupBy($subtotal_by->database_field_name)->pluck($subtotal_by->database_field_name);

            foreach($subtotal_ids as $subtotal_id) {
                $subtotal_query = clone $bill_query;
                $subtotal_query->where($subtotal_by->database_field_name, $subtotal_id);
                if($subtotal_by->database_field_name == 'charge_account_id') {
                    $sort_options = $invoiceRepo->GetSortOrderById($subtotal_id);
                    $temp_account = $accountRepo->GetById($subtotal_id);
                    $subtotal_string = $temp_account->account_number . ' ' . $temp_account->name;
                } else if($subtotal_by->database_field_name == 'charge_reference_value') {
                    $subtotal_string = $accountRepo->GetById($account_id)->custom_field . ' ' . $subtotal_id;
                } else
                    $subtotal_string = $subtotal_by->friendly_name . ' ' . $subtotal_id;
                foreach($sort_options as $option) {
                    $subtotal_query->orderBy($option->database_field_name);
                }
                $bills[$subtotal_string] = new \stdClass();
                $bills[$subtotal_string]->bills = $subtotal_query->get();
            }
        }

        return $bills;
    }

    public function GetChartMonthly($dateGroupBy, $startDate, $endDate, $groupBy = false) {

        $bills = Bill::whereDate('time_pickup_scheduled', '>=', $startDate)
            ->whereDate('time_pickup_scheduled', '<=', $endDate)
            ->leftJoin('employees', 'employees.employee_id', '=', 'bills.pickup_driver_id')
            ->leftJoin('contacts', 'employees.contact_id', '=', 'contacts.contact_id')
            ->select(
                DB::raw('sum(amount) as amount'),
                DB::raw('count(*) as count'),
                'charge_account_id',
                DB::raw('date_format(time_pickup_scheduled, "%Y-%m-%d (%a)") as day'),
                'delivery_type',
                DB::raw('concat(contacts.first_name, " ", contacts.last_name) as employee_name'),
                'employee_id',
                DB::raw('concat(year(time_pickup_scheduled), "/", month(time_pickup_scheduled), " - ", monthname(time_pickup_scheduled)) as month'),
                'pickup_driver_id',
                DB::raw('date_format(time_pickup_scheduled, "%Y") as year'),
                DB::raw('sum(case when pickup_driver_id = employee_id and delivery_driver_id = employee_id then round(amount * pickup_driver_commission, 2) + round(amount * delivery_driver_commission, 2) when pickup_driver_id = employee_id then round(amount * pickup_driver_commission, 2) when delivery_driver_id = employee_id then round(amount * delivery_driver_id, 2) end) as driver_income')
            );
        if($groupBy === 'none')
            $bills->groupBy($dateGroupBy);
        else
            $bills->groupBy($dateGroupBy, $groupBy);

        return $bills->get();
    }

    public function IsReadOnly($bill_id) {
        $bill = $this->GetById($bill_id);

        if(isset($bill->invoice_id) && isset($bill->pickup_manifest_id) && isset($bill->delivery_manifest_id))
            return true;
        else
            return false;
    }
    
    public function Insert($bill) {
        $completeBill = $this->CheckRequiredFields($bill);
        $new = new Bill;

        return ($new->create($completeBill));
    }

    public function Delete($id) {
        $bill = $this->GetById($id);
        if($this->IsReadOnly($bill->bill_id))
            throw new \Exception('Unable to delete bill after it has been invoiced or manifested');

        $bill->delete();
        $addressRepo = new AddressRepo();
        $addressRepo->Delete($bill->pickup_address_id);
        $addressRepo->Delete($bill->delivery_address_id);
        //TODO: Delete associated chargebacks

        return;
    }

    public function Update($bill) {
        $completeBill = $this->CheckRequiredFields((array) $bill);
        $old = $this->GetById($bill['bill_id']);
        $fields = array(
            'amount',
            'bill_number',
            'charge_account_id',
            'charge_reference_value',
            'chargeback_id',
            'delivery_account_id',
            'delivery_address_id',
            'delivery_driver_commission',
            'delivery_driver_id',
            'delivery_reference_value',
            'delivery_type',
            'description',
            'incomplete_fields',
            'interliner_cost',
            'interliner_cost_to_customer',
            'interliner_id',
            'interliner_reference_value',
            'is_min_weight_size',
            'is_pallet',
            'packages',
            'payment_id',
            'payment_type_id',
            'percentage_complete',
            'pickup_account_id',
            'pickup_address_id',
            'pickup_driver_commission',
            'pickup_driver_id',
            'pickup_reference_value',
            'skip_invoicing',
            'time_call_received',
            'time_dispatched',
            'time_delivered',
            'time_delivery_scheduled',
            'time_picked_up',
            'time_pickup_scheduled',
            'use_imperial',
        );

        if($this->IsReadOnly($old->bill_id))
            throw new \Exception('Unable to edit bill after it has been invoiced or manifested');

        foreach($fields as $field)
            $old->$field = $completeBill[$field];

        $old->save();

        return $old;
    }

    public function CountByInvoiceId($invoiceId) {
        $bills = \DB::table("bills")->select(\DB::raw('count(bill_id) as bill_count'))
            ->where('invoice_id', '=', $invoiceId)
            ->get();

        return $bills[0]->bill_count;
    }

    public function CountByManifestId($manifest_id) {
        $count = Bill::where('pickup_manifest_id', $manifest_id)
                ->orWhere('delivery_manifest_id', $manifest_id)
                ->count();

        return $count;
    }

    public function GetByManifestId($manifest_id) {
        $bills = Bill::where('pickup_manifest_id', $manifest_id)
            ->orWhere('delivery_manifest_id', $manifest_id)
            ->select(
                'bill_id',
                'bill_number',
                'time_pickup_scheduled',
                DB::raw('format(amount, 2) as amount'),
                'charge_account_id',
                'delivery_type',
                DB::raw('case when pickup_manifest_id = ' . $manifest_id . ' and delivery_manifest_id = ' . $manifest_id . ' then "Pickup And Delivery" when pickup_manifest_id = ' . $manifest_id . ' then "Pickup Only" when delivery_manifest_id = ' . $manifest_id . ' then "Delivery Only" end as type'),
                DB::raw('DATE_FORMAT(time_pickup_scheduled, "%Y-%m-%d") as day'),
                DB::raw('case when pickup_manifest_id = ' . $manifest_id . ' and delivery_manifest_id = ' . $manifest_id . ' then round(amount * pickup_driver_commission, 2) + round(amount * delivery_driver_commission, 2) when pickup_manifest_id = ' . $manifest_id . ' then round(amount * pickup_driver_commission, 2) when delivery_manifest_id = ' . $manifest_id . ' then round(amount * delivery_driver_commission, 2) end as driver_income')
            )
            ->orderBy('time_pickup_scheduled')
            ->orderBy('bill_id');

        return $bills->get();
    }

    public function GetManifestOverviewById($manifest_id) {
        $bills = Bill::where('pickup_manifest_id', $manifest_id)
            ->orWhere('delivery_manifest_id', $manifest_id)
            ->select(DB::raw('DATE(time_pickup_scheduled) as time_pickup_scheduled'),
                    DB::raw('sum(case when pickup_manifest_id = ' . $manifest_id . ' then round(amount * pickup_driver_commission, 2) else 0 end) as pickup_amount'),
                    DB::raw('sum(case when delivery_manifest_id = ' . $manifest_id . ' then round(amount * delivery_driver_commission, 2) else 0 end) as delivery_amount'),
                    DB::raw('count(case when pickup_manifest_id = ' . $manifest_id . ' then 1 else NULL end) as pickup_count'),
                    DB::raw('count(case when delivery_manifest_id = ' . $manifest_id . ' then 1 else NULL end) as delivery_count'))
            ->groupBy(DB::raw('DATE(time_pickup_scheduled)'))
            ->get();

        return $bills;
    }

    public function GetDriverTotalByManifestId($manifest_id) {
        $total = Bill::where('pickup_manifest_id', $manifest_id)
                ->orWhere('delivery_manifest_id', $manifest_id)
                ->select(DB::raw('sum(case ' .
                    'when pickup_manifest_id = ' . $manifest_id . ' and delivery_manifest_id = ' . $manifest_id . ' then round(amount * pickup_driver_commission, 2) + round(amount * delivery_driver_commission, 2) ' .
                    'when pickup_manifest_id = ' . $manifest_id . ' then round(amount * pickup_driver_commission, 2) ' .
                    'when delivery_manifest_id  = ' . $manifest_id . ' then round(amount * delivery_driver_commission, 2) end) as total'))
                ->pluck('total');

        return $total[0];
    }

    public function CountByDriverBetweenDates($driver_id, $start_date, $end_date) {
        $count = Bill::whereDate('time_pickup_scheduled', '>=', $start_date)
                ->whereDate('time_pickup_scheduled', '<=', $end_date)
                ->where('percentage_complete', 1)
                ->where(function($query) use ($driver_id) {
                    $query->where('pickup_driver_id', '=', $driver_id)
                    ->where('pickup_manifest_id', null)
                    ->orWhere('delivery_driver_id', '=', $driver_id)
                    ->where('delivery_manifest_id', null);
                });

        return $count->count();
    }

    public function CountByDriver($driverId) {
	    $count = Bill::where('pickup_driver_id', '=', $driverId)
            ->orWhere('delivery_driver_id', '=', $driverId)
            ->count();

	    return $count;
    }

    public function GetInvoiceSubtotalByField($invoice_id, $field_name, $field_value) {
        $subtotal = Bill::where('invoice_id', $invoice_id)
            ->where($field_name, $field_value)
            ->value(DB::raw('round(sum(amount + case when interliner_cost_to_customer is not null then interliner_cost_to_customer else 0 end), 2)'));

        return $subtotal;
    }

    public function GetAmountByInvoiceId($invoice_id) {
        $amount = Bill::where('invoice_id', $invoice_id)
            ->value(DB::raw('sum(amount + case when interliner_cost_to_customer is not null then interliner_cost_to_customer else 0 end)'));

        return $amount;
    }

    public function SetBillPickupOrDeliveryTime($bill_id, $type, $time) {
        $old = $this->GetById($bill_id);

        if($type === 'pickup')
            $old->time_picked_up = $time;
        else if ($type === 'delivery')
            $old->time_delivered = $time;

        $old = $this->CheckRequiredFields($old);
        $old->save();

        return $old;
    }

    // Private Functions

    //Checks the completion level of the bill prior to each insert/update
    //In order to support partial updates, such as assigning a driver or setting ONLY a pickup/delivery time, the function must support
    //both objects and array form $bill parameters
    private function CheckRequiredFields($bill) {
		$requiredFields = [
			'amount',
			'bill_number',
			'delivery_driver_commission',
			'delivery_driver_id',
			'delivery_type',
			'pickup_driver_id',
			'pickup_driver_commission',
			'time_pickup_scheduled',
			'time_delivery_scheduled',
			'time_call_received',
			'time_dispatched',
		];

        $paymentRepo = new PaymentRepo();

        $interlinerId = is_object($bill) ? $bill->interlinerId : $bill['interliner_id'];
        $paymentTypeId = is_object($bill) ? $bill->payment_type_id : $bill['payment_type_id'];

        $paymentType = $paymentRepo->GetPaymentType($paymentTypeId);

		if($interlinerId != "")
			$requiredFields = array_merge($requiredFields, ['interliner_id', 'interliner_reference_value', 'interliner_cost', 'interliner_cost_to_customer']);

		if($paymentType->name === 'Account')
			$requiredFields = array_merge($requiredFields, ['charge_account_id']);
		elseif($paymentType->name === 'Driver')
			$requiredFields = array_merge($requiredFields, ['charge_driver_id']);
		elseif($paymentType->is_prepaid)
			$requiredFields = array_merge($requiredFields, ['payment_id']);

        $incompleteFields = [];
        if(is_object($bill))
            foreach($requiredFields as $field) {
                if(empty($bill->$field))
                    array_push($incompleteFields, $field);
            }
        else
            foreach($requiredFields as $field) {
                if ($bill[$field] == null || $bill[$field] == '')
                    array_push($incompleteFields, $field);
            }

        $percentageComplete = number_format((count($requiredFields) - count($incompleteFields)) / count($requiredFields), 2);

        if(is_object($bill)) {
            Activity('system_debug')->log('is_object');
            $bill->percentage_complete = $percentageComplete;
            $bill->incomplete_fields = $incompleteFields;
            return $bill;
        }
        else {
            return array_merge($bill, ['incomplete_fields' => $percentageComplete === 1 ? null : json_encode($incompleteFields), 'percentage_complete' => $percentageComplete]);
        }
	}
}
