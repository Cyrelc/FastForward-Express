<?php
namespace App\Http\Repos;

use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Filters\DateBetween;
use App\Http\Filters\NumberBetween;
use App\Http\Filters\BillFilters\Dispatch;
use App\Http\Filters\IsNull;

use App\Account;
use App\Bill;
use DB;

class BillRepo {
    public function AssignToDriver($bill_id, $employee) {
        $old = $this->GetById($bill_id);

        $old->pickup_driver_id = $employee->employee_id;
        $old->delivery_driver_id = $employee->employee_id;
        $old->pickup_driver_commission = $employee->pickup_commission / 100;
        $old->delivery_driver_commission = $employee->delivery_commission / 100;
        $old->time_dispatched = new \DateTime();

        $old = $this->CheckRequiredFields($old);

        $old->save();

        return $old;
    }

    public function CountByDriver($driverId) {
	    $count = Bill::where('pickup_driver_id', '=', $driverId)
            ->orWhere('delivery_driver_id', '=', $driverId)
            ->count();

	    return $count;
    }

    public function CountByDriverBetweenDates($driver_id, $start_date, $end_date) {
        $count = Bill::whereDate('time_pickup_scheduled', '>=', $start_date)
                ->whereDate('time_pickup_scheduled', '<=', $end_date)
                ->where('percentage_complete', 100)
                ->where(function($query) use ($driver_id) {
                    $query->where('pickup_driver_id', '=', $driver_id)
                    ->where('pickup_manifest_id', null)
                    ->orWhere('delivery_driver_id', '=', $driver_id)
                    ->where('delivery_manifest_id', null);
                });

        return $count->count();
    }

    public function CountByInvoiceId($invoiceId) {
        $billCount = Bill::where('invoice_id', '=', $invoiceId)
            ->count();

        return $billCount;
    }

    public function CountByManifestId($manifest_id) {
        $count = Bill::where('pickup_manifest_id', $manifest_id)
                ->orWhere('delivery_manifest_id', $manifest_id)
                ->count();

        return $count;
    }

    public function Delete($id) {
        $bill = $this->GetById($id);
        if(isset($bill->invoice_id) || isset($bill->pickup_manifest_id) || isset($bill->delivery_manifest_id))
            throw new \Exception('Unable to delete bill after it has been invoiced or manifested');

        $bill->delete();
        $addressRepo = new AddressRepo();
        $addressRepo->Delete($bill->pickup_address_id);
        $addressRepo->Delete($bill->delivery_address_id);
        //TODO: Delete associated chargebacks

        return;
    }

    public function GetAmountByInvoiceId($invoice_id) {
        $amount = Bill::where('invoice_id', $invoice_id)
            ->value(DB::raw('sum(amount + case when interliner_cost_to_customer is not null then interliner_cost_to_customer else 0 end)'));

        return $amount;
    }

    public function GetById($billId) {
	    $bill = Bill::where('bill_id', $billId)->first();

	    return $bill;
    }

    public function GetByInvoiceId($invoiceId) {
        $invoiceRepo = new InvoiceRepo();
        $accountRepo = new AccountRepo();

        $invoice = $invoiceRepo->GetById($invoiceId);

        $invoiceSortOptions = $accountRepo->GetInvoiceSortOrder($invoice->account_id);

        $subtotalBy = null;
        foreach($invoiceSortOptions as $invoiceSortOption)
            if(isset($invoiceSortOption->group_by) && filter_var($invoiceSortOption->group_by, FILTER_VALIDATE_BOOLEAN))
                $subtotalBy = $invoiceSortOption;

        $billQuery = Bill::where('invoice_id', $invoiceId)
            ->join('addresses as pickup', 'pickup.address_id', '=', 'bills.pickup_address_id')
            ->join('addresses as delivery', 'delivery.address_id', '=', 'bills.delivery_address_id')
            ->join('accounts', 'accounts.account_id', '=', 'bills.charge_account_id')
            ->join('selections', 'selections.value', '=', 'bills.delivery_type')
            ->select(
                'bill_id',
                DB::raw('format(amount + case when interliner_cost_to_customer is not null then interliner_cost_to_customer else 0 end, 2) as amount'),
                'bill_number',
                'time_pickup_scheduled',
                'charge_account_id',
                'pickup_account_id',
                'delivery_account_id',
                'charge_reference_value',
                'selections.name as delivery_type',
                'pickup.name as pickup_address_name',
                'delivery.name as delivery_address_name',
                'accounts.name as charge_account_name'
            );

        $bills = array();
        if($subtotalBy == NULL) {
            foreach($invoiceSortOptions as $option) {
                $billQuery->orderBy($option->database_field_name);
            }

            $bills[0] = new \stdClass();
            $bills[0]->bills = $billQuery->get();
        } else {
            if($subtotalBy->database_field_name === 'time_pickup_scheduled')
                $subtotalIds = Bill::where('invoice_id', $invoiceId)->select(DB::raw('date(time_pickup_scheduled) as pickup_date'))->groupBy('pickup_date')->pluck('pickup_date');
            else
                $subtotalIds = Bill::where('invoice_id', $invoiceId)->groupBy($subtotalBy->database_field_name)->pluck($subtotalBy->database_field_name);

            foreach($subtotalIds as $subtotalId) {
                $subtotalQuery = clone $billQuery;
                if($subtotalBy->database_field_name === 'time_pickup_scheduled')
                    $subtotalQuery->whereDate($subtotalBy->database_field_name, $subtotalId);
                else
                    $subtotalQuery->where($subtotalBy->database_field_name, $subtotalId);

                if($subtotalBy->database_field_name == 'charge_account_id') {
                    $invoiceSortOptions = $accountRepo->GetInvoiceSortOrder($subtotalId);
                    $tempAccount = $accountRepo->GetById($subtotalId);
                    $subtotalString = $tempAccount->account_number . ' ' . $tempAccount->name;
                } else {
                    if($subtotalBy->database_field_name == 'charge_reference_value')
                        $subtotalString = $accountRepo->GetById($invoice->account_id)->custom_field . ' ' . $subtotalId;
                    else
                        $subtotalString = $subtotalBy->friendly_name . ' ' . $subtotalId;
                }
                foreach($invoiceSortOptions as $option) {
                    $subtotalQuery->orderBy($option->database_field_name);
                }
                $bills[$subtotalString] = new \stdClass();
                $bills[$subtotalString]->bills = $subtotalQuery->get();
            }
        }

        return $bills;
    }

    public function GetByManifestId($manifest_id) {
        $bills = Bill::where('pickup_manifest_id', $manifest_id)
            ->orWhere('delivery_manifest_id', $manifest_id)
            ->leftjoin('selections', 'selections.value', '=', 'delivery_type')
            ->select(
                'bill_id',
                'bill_number',
                'time_pickup_scheduled',
                DB::raw('format(amount, 2) as amount'),
                'charge_account_id',
                'selections.name as delivery_type',
                DB::raw('case when pickup_manifest_id = ' . $manifest_id . ' and delivery_manifest_id = ' . $manifest_id . ' then "Pickup And Delivery" when pickup_manifest_id = ' . $manifest_id . ' then "Pickup Only" when delivery_manifest_id = ' . $manifest_id . ' then "Delivery Only" end as type'),
                DB::raw('DATE_FORMAT(time_pickup_scheduled, "%Y-%m-%d") as day'),
                DB::raw('case when pickup_manifest_id = ' . $manifest_id . ' and delivery_manifest_id = ' . $manifest_id . ' then round(amount * pickup_driver_commission, 2) + round(amount * delivery_driver_commission, 2) when pickup_manifest_id = ' . $manifest_id . ' then round(amount * pickup_driver_commission, 2) when delivery_manifest_id = ' . $manifest_id . ' then round(amount * delivery_driver_commission, 2) end as driver_income')
            )
            ->orderBy('time_pickup_scheduled')
            ->orderBy('bill_id');

        return $bills->get();
    }

    public function GetCalendarHeatChart($accountId) {
        $bills = Bill::select(
            DB::raw('date_format(time_pickup_scheduled, "%Y-%m-%d") as day'),
            DB::raw('count(*) as value')
        )->groupBy('day');

        return $bills->get();
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
                DB::raw('date_format(time_pickup_scheduled, "%Y-%m - %b") as month'),
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

    public function GetInvoiceSubtotalByField($invoiceId, $fieldName, $fieldValue) {
        $subtotal = Bill::where('invoice_id', $invoiceId);

        if($fieldName === 'time_pickup_scheduled')
            $subtotal->whereDate($fieldName, explode(" ", $fieldValue)[0]);
        else
            $subtotal->where($fieldName, $fieldValue);

        return $subtotal->sum(DB::raw('round(amount + case when interliner_cost_to_customer is not null then interliner_cost_to_customer else 0 end, 2)'));
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

    public function GetMonthlyTotals($date) {
        $bills = Bill::whereDate('time_pickup_scheduled', '>=', $date)
        ->select(
            DB::raw('sum(amount) as income'),
            DB::raw('date_format(time_pickup_scheduled, "%Y-%m") as month'),
            DB::raw('sum(interliner_cost) as interliner_cost'),
            DB::raw('sum(interliner_cost_to_customer) as interliner_cost_to_customer')
        )
        ->groupBy('month');

        return $bills->get();
    }

    public function GetPrepaidAccountsReceivable($startDate, $endDate) {
        $bills = Bill::leftjoin('payment_types', 'bills.payment_type_id', '=', 'payment_types.payment_type_id')
            ->where('payment_types.is_prepaid', 1)
            ->where('time_pickup_scheduled', '>=', $startDate->format('Y-m-01'))
            ->where('time_pickup_scheduled', '<=', $endDate->format('Y-m-t'))
            ->select(
                'bills.payment_type_id',
                'payment_types.name as payment_type_name',
                DB::raw('sum(case when amount is null then 0 else amount end) as amount')
            )->groupBy('payment_type_id');

        return $bills->get();
    }

    public function GetPrepaidMonthlyTotals($date) {
        $paymentRepo = new PaymentRepo();
        $prepaidOptions = $paymentRepo->GetPrepaidPaymentTypes();
        $prepaidOptionIds = [];
        foreach($prepaidOptions as $option) {
            array_push($prepaidOptionIds, $option->payment_type_id);
        }

        $bills = Bill::whereDate('time_pickup_scheduled', '>=', $date)
        ->whereIn('payment_type_id', $prepaidOptionIds)
        ->select(
            DB::raw('sum(amount) as prepaid_income'),
            DB::raw('date_format(time_pickup_scheduled, "%Y-%m") as month')
        )
        ->groupBy('month');

        return $bills->get();
    }

    public function GetRepeatingBills($repeatIntervalId) {
        $bills = Bill::where('repeat_interval', '=', $repeatIntervalId);

        return $bills->get();
    }

    public function GetRepeatingBillsForToday() {
        $selectionsRepo = new SelectionsRepo();
        $dailyId = $selectionsRepo->GetSelectionByTypeAndValue('repeat_interval', 'daily')->selection_id;
        $weeklyId = $selectionsRepo->GetSelectionByTypeAndValue('repeat_interval', 'weekly')->selection_id;

        $recurringBills = Bill::where('repeat_interval', $dailyId)
            ->orWhere(function ($query) use ($weeklyId) {
                $currentDayOfTheWeek = date('w') + 1;
                $query->where('repeat_interval', $weeklyId)
                    ->whereRaw('dayofweek(time_pickup_scheduled) = ' . $currentDayOfTheWeek);
            });

        return $recurringBills->get();
    }

    public function Insert($bill) {
        $completeBill = $this->CheckRequiredFields($bill);
        $new = new Bill;

        return $new->create($completeBill);
    }

    public function IsReadOnly($bill_id) {
        $bill = $this->GetById($bill_id);

        if(isset($bill->invoice_id) && isset($bill->pickup_manifest_id) && isset($bill->delivery_manifest_id))
            return true;
        else
            return false;
    }

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
                ->leftJoin('selections as deliveryType', 'deliveryType.value', '=', 'bills.delivery_type')
                ->leftJoin('selections as repeatInterval', 'repeatInterval.selection_id', '=', 'bills.repeat_interval')
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
                    'deliveryType.name as delivery_type',
                    'description',
                    DB::raw('coalesce(invoice_id, pickup_manifest_id, delivery_manifest_id) is null as editable'),
                    'interliner_cost',
                    'interliner_cost_to_customer',
                    'interliners.interliner_id',
                    'interliners.name as interliner_name',
                    'invoice_id',
                    'bills.payment_type_id',
                    'payment_types.name as payment_type',
                    'parent_accounts.account_id as parent_account_id',
                    DB::raw('case when accounts.can_be_parent = 1 then concat(accounts.account_id, " - ", accounts.name) when accounts.can_be_parent = 0 then concat(accounts.parent_account_id, " - ", parent_accounts.name) end as parent_account'),
                    'percentage_complete',
                    'pickup_address.formatted as pickup_address_formatted',
                    'pickup_address.lat as pickup_address_lat',
                    'pickup_address.lng as pickup_address_lng',
                    'pickup_address.name as pickup_address_name',
                    'pickup_driver_id',
                    DB::raw('concat(pickup_employee_contact.first_name, " ", pickup_employee_contact.last_name) as pickup_employee_name'),
                    'pickup_manifest_id',
                    'repeat_interval',
                    'repeatInterval.name as repeat_interval_name',
                    'time_pickup_scheduled',
                    'time_delivery_scheduled',
                    'time_picked_up',
                    'time_delivered'
                );

            $filteredBills = QueryBuilder::for($bills)
                ->allowedFilters([
                    AllowedFilter::custom('amount', new NumberBetween),
                    'bill_number',
                    AllowedFilter::exact('charge_account_id'),
                    AllowedFilter::custom('dispatch', new Dispatch),
                    AllowedFilter::exact('delivery_driver_id'),
                    AllowedFilter::exact('interliner_id', 'bills.interliner_id'),
                    AllowedFilter::exact('invoice_id'),
                    AllowedFilter::custom('invoiced', new IsNull, 'invoice_id'),
                    AllowedFilter::exact('invoice_interval'),
                    AllowedFilter::exact('parent_account_id', 'charge_account.parent_account_id'),
                    AllowedFilter::exact('skip_invoicing'),
                    AllowedFilter::custom('time_pickup_scheduled', new DateBetween),
                    AllowedFilter::custom('time_delivery_scheduled', new DateBetween),
                    AllowedFilter::exact('payment_type_id', 'bills.payment_type_id'),
                    AllowedFilter::custom('percentage_complete', new NumberBetween),
                    AllowedFilter::exact('pickup_driver_id'),
                    AllowedFilter::exact('repeat_interval')
                ]);

            return $filteredBills->get();
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
            'repeat_interval',
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
            throw new \Exception('Unable to edit bill after it has been invoiced and manifested');

        foreach($fields as $field)
            $old->$field = $completeBill[$field];

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

        $percentageComplete = (int)((count($requiredFields) - count($incompleteFields)) / count($requiredFields) * 100);

        if(is_object($bill)) {
            Activity('system_debug')->log('is_object');
            $bill->percentage_complete = $percentageComplete;
            $bill->incomplete_fields = $incompleteFields;
            return $bill;
        }
        else {
            return array_merge($bill, ['incomplete_fields' => $percentageComplete === 100 ? null : json_encode($incompleteFields), 'percentage_complete' => $percentageComplete]);
        }
	}
}
