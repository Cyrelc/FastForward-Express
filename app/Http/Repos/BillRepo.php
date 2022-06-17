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
use App\LineItem;
use DB;
use Illuminate\Support\Facades\Auth;

class BillRepo {
    private $myAccounts;
    private $employeeId;

    public function __construct() {
        $user = Auth::user();

        $accountRepo = new AccountRepo();
        $this->myAccounts = $user->accountUsers ? $accountRepo->GetMyAccountIds($user, $user->can('bills.view.basic.children')) : null;
        $this->employeeId = $user->employee ? $user->employee->employee_id : null;
    }

    public function AssignToDriver($billId, $employee) {
        $bill = $this->GetById($billId);

        $bill->pickup_driver_id = $employee ? $employee->employee_id : null;
        $bill->delivery_driver_id = $employee ? $employee->employee_id : null;
        $bill->pickup_driver_commission = $employee ? $employee->pickup_commission / 100 : null;
        $bill->delivery_driver_commission = $employee ? $employee->delivery_commission / 100 : null;
        $bill->time_dispatched = $employee ? new \DateTime() : null;

        $bill->save();

        return $bill;
    }

    public function CountByDriver($driverId) {
	    $count = Bill::where('pickup_driver_id', '=', $driverId)
            ->orWhere('delivery_driver_id', '=', $driverId)
            ->count();

	    return $count;
    }

    public function CountByInvoiceId($invoiceId) {
        $billCount = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->where('invoice_id', '=', $invoiceId)
            ->distinct('charges.bill_id');

        return $billCount->count();
    }

    public function CountByManifestId($manifestId) {
        $count = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
                ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
                ->where('pickup_manifest_id', $manifestId)
                ->orWhere('delivery_manifest_id', $manifestId)
                ->distinct('bills.bill_id');

        return $count->count();
    }

    public function Delete($billId) {
        $addressRepo = new AddressRepo();
        $chargeRepo = new ChargeRepo();

        $bill = $this->GetById($billId);

        $charges = $chargeRepo->DeleteByBillId($billId);
        $bill->delete();

        $addressRepo->Delete($bill->pickup_address_id);
        $addressRepo->Delete($bill->delivery_address_id);

        return;
    }

    public function GetAmendmentsByInvoiceId($invoiceId) {
        $billQuery = LineItem::where('invoice_id', $invoiceId)
            ->leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->join('addresses as pickup', 'pickup.address_id', '=', 'bills.pickup_address_id')
            ->join('addresses as delivery', 'delivery.address_id', '=', 'bills.delivery_address_id')
            ->join('accounts', 'accounts.account_id', '=', 'charges.charge_account_id')
            ->join('selections', 'selections.value', '=', 'bills.delivery_type')
            ->where('amendment_number', '!=', null)
            ->select(
                'accounts.name as charge_account_name',
                DB::raw('sum(price) as amount'),
                'bills.bill_id',
                'bill_number',
                'charges.charge_id as charge_id',
                'charge_account_id',
                'charge_reference_value',
                'delivery_account_id',
                'delivery.name as delivery_address_name',
                'pickup_account_id',
                'pickup.name as pickup_address_name',
                'selections.name as delivery_type',
                'time_pickup_scheduled'
            )->groupBy('bills.bill_id');

        return $billQuery->get();
    }

    public function GetById($billId, $permissions = null) {
        $bill = Bill::where('bill_id', $billId);

        if($permissions)
            $bill->select(
                array_merge(
                    $permissions['viewBasic'] ? Bill::$basicFields : [],
                    $permissions['viewDispatch'] ? Bill::$dispatchFields : [],
                    $permissions['viewBilling'] ? Bill::$billingFields : [],
                    Bill::$readOnlyFields
                )
            );

	    return $bill->first();
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

        $billQuery = LineItem::where('invoice_id', $invoiceId)
            ->leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->join('addresses as pickup', 'pickup.address_id', '=', 'bills.pickup_address_id')
            ->join('addresses as delivery', 'delivery.address_id', '=', 'bills.delivery_address_id')
            ->join('accounts', 'accounts.account_id', '=', 'charges.charge_account_id')
            ->join('selections', 'selections.value', '=', 'bills.delivery_type')
            ->where('amendment_number', null)
            ->select(
                'accounts.account_id',
                'accounts.name as charge_account_name',
                DB::raw('sum(price) as amount'),
                'bills.bill_id',
                'bill_number',
                'charges.charge_id as charge_id',
                'charge_account_id',
                'charge_reference_value',
                'delivery_account_id',
                'delivery.name as delivery_address_name',
                'pickup_account_id',
                'pickup.name as pickup_address_name',
                'selections.name as delivery_type',
                'time_pickup_scheduled'
            )->groupBy('bills.bill_id');

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
                $subtotalIds = Bill::leftJoin('charges', 'charges.bill_id', '=', 'bills.bill_id')
                    ->leftJoin('line_items', 'line_items.charge_id', '=', 'charges.charge_id')
                    ->where('invoice_id', $invoiceId)
                    ->groupBy($subtotalBy->database_field_name)
                    ->orderBy($subtotalBy->database_field_name)
                    ->pluck($subtotalBy->database_field_name);

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
                        $subtotalString = $accountRepo->GetById($invoice->account_id)->custom_field . ': ' . $subtotalId;
                    else
                        $subtotalString = $subtotalBy->friendly_name . ': ' . $subtotalId;
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

    public function GetByManifestId($manifestId) {
        $bills = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->where('pickup_manifest_id', $manifestId)
            ->orWhere('delivery_manifest_id', $manifestId)
            ->leftjoin('selections', 'selections.value', '=', 'delivery_type')
            ->select(
                DB::raw('sum(driver_amount) as amount'),
                'bills.bill_id',
                'bill_number',
                'time_pickup_scheduled',
                'selections.name as delivery_type',
                DB::raw('case when pickup_manifest_id = ' . $manifestId . ' and delivery_manifest_id = ' . $manifestId . ' then "Pickup And Delivery" when pickup_manifest_id = ' . $manifestId . ' then "Pickup Only" when delivery_manifest_id = ' . $manifestId . ' then "Delivery Only" end as type'),
                DB::raw('DATE_FORMAT(time_pickup_scheduled, "%Y-%m-%d") as day'),
                DB::raw('round(sum(case when pickup_manifest_id = ' . $manifestId . ' and delivery_manifest_id = ' . $manifestId . ' then driver_amount * pickup_driver_commission + driver_amount * delivery_driver_commission when pickup_manifest_id = ' . $manifestId . ' then driver_amount * pickup_driver_commission when delivery_manifest_id = ' . $manifestId . ' then driver_amount * delivery_driver_commission end), 2) as driver_income')
            )->orderBy('time_pickup_scheduled')
            ->orderBy('bills.bill_id')
            ->groupBy('bills.bill_id');

        return $bills->get();
    }

    public function GetCalendarHeatChart($accountId) {
        $bills = Bill::select(
            DB::raw('date_format(time_pickup_scheduled, "%Y-%m-%d") as day'),
            DB::raw('count(*) as value')
        )->groupBy('day');

        return $bills->get();
    }

    /**
     * Gets a list of all bills fitting criteria
     * @param dateGroupBy 'day', 'month', or 'year'
     * @param startDate php parseable date string
     * @param endDate php parseable date string
     * @param groupBy whether or not we are grouping by anything other than date
     * @param filterBy a key = value dictionary to only return bills matching that parameter Ex. {'column' => 'account_id', 'value' => 11}
     */
    public function GetChartMonthly($dateGroupBy, $startDate, $endDate, $groupBy = false, $filterBy = false) {
        $bills = Bill::whereDate('time_pickup_scheduled', '>=', $startDate)
            ->whereDate('time_pickup_scheduled', '<=', $endDate)
            ->leftJoin('employees', 'employees.employee_id', '=', 'bills.pickup_driver_id')
            ->leftJoin('contacts', 'employees.contact_id', '=', 'contacts.contact_id')
            ->leftJoin('charges', 'charges.bill_id', '=', 'bills.bill_id')
            ->leftJoin('line_items', 'line_items.charge_id', '=', 'charges.charge_id')
            ->select(
                DB::raw('sum(price) as amount'),
                DB::raw('count(distinct(bills.bill_id)) as count'),
                'charge_account_id',
                DB::raw('date_format(time_pickup_scheduled, "%Y-%m-%d (%a)") as day'),
                'delivery_type',
                DB::raw('concat(contacts.first_name, " ", contacts.last_name) as employee_name'),
                'employees.employee_id',
                DB::raw('date_format(time_pickup_scheduled, "%Y-%m - %b") as month'),
                'pickup_driver_id',
                DB::raw('date_format(time_pickup_scheduled, "%Y") as year'),
                DB::raw('sum(case when pickup_driver_id = employees.employee_id and delivery_driver_id = employees.employee_id then round(driver_amount * pickup_driver_commission, 2) + round(driver_amount * delivery_driver_commission, 2) when pickup_driver_id = employees.employee_id then round(driver_amount * pickup_driver_commission, 2) when delivery_driver_id = employees.employee_id then round(driver_amount * delivery_driver_id, 2) end) as driver_income')
            );

        if($filterBy) {
            $bills->where($filterBy['column'], $filterBy['value']);
        }

        if($groupBy === 'none')
            $bills->groupBy($dateGroupBy);
        else
            $bills->groupBy($dateGroupBy, $groupBy);

        return $bills->get();
    }

    public function GetForDispatch($req) {
        $bills = Bill::leftJoin('addresses as delivery_address', 'delivery_address.address_id', '=', 'bills.delivery_address_id')
            ->leftJoin('addresses as pickup_address', 'pickup_address.address_id', '=', 'bills.pickup_address_id')
            ->select(
                'bill_id',
                'delivery_address.lat as delivery_address_lat',
                'delivery_address.lng as delivery_address_lng',
                'delivery_driver_id',
                'pickup_address.lat as pickup_address_lat',
                'pickup_address.lng as pickup_address_lng',
                'pickup_driver_id',
                'time_delivered',
                'time_delivery_scheduled',
                'time_dispatched',
                'time_pickup_scheduled',
                'time_picked_up',
            );

        $filteredBills = QueryBuilder::for($bills)
            ->allowedFilters([
                AllowedFilter::custom('dispatch', new Dispatch),
                AllowedFilter::custom('time_pickup_scheduled', new DateBetween),
            ]);

        return $filteredBills->get();
    }

    public function GetInvoiceSubtotalByField($invoiceId, $fieldName, $fieldValue) {
        $subtotal = LineItem::where('invoice_id', $invoiceId)
            ->leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id');

        if($fieldName === 'time_pickup_scheduled')
            $subtotal->whereDate($fieldName, explode(" ", $fieldValue)[0]);
        else
            $subtotal->where($fieldName, $fieldValue);

        return $subtotal->sum('price');
    }

    public function GetManifestOverviewById($manifestId) {
        $bills = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->where('pickup_manifest_id', $manifestId)
            ->orWhere('delivery_manifest_id', $manifestId)
            ->select(
                DB::raw('DATE(time_pickup_scheduled) as time_pickup_scheduled'),
                DB::raw('DATE(time_pickup_scheduled) as comparison_time'),
                DB::raw('sum(case when pickup_manifest_id = ' . $manifestId . ' then round(driver_amount * pickup_driver_commission, 2) else 0 end) as pickup_amount'),
                DB::raw('sum(case when delivery_manifest_id = ' . $manifestId . ' then round(driver_amount * delivery_driver_commission, 2) else 0 end) as delivery_amount'),
                DB::raw('(select count(distinct charges.bill_id) from line_items left join charges on charges.charge_id = line_items.charge_id left join bills on bills.bill_id = charges.bill_id where pickup_manifest_id = ' . $manifestId . ' and DATE(bills.time_pickup_scheduled) = comparison_time) as pickup_count'),
                DB::raw('(select count(distinct charges.bill_id) from line_items left join charges on charges.charge_id = line_items.charge_id left join bills on bills.bill_id = charges.bill_id where delivery_manifest_id = ' . $manifestId . ' and DATE(bills.time_pickup_scheduled) = comparison_time) as delivery_count'),
            )
            ->groupBy(DB::raw('date(time_pickup_scheduled)'))
            ->orderBy(DB::raw('date(time_pickup_scheduled)'))
            ->get();

        return $bills;
    }

    public function GetMonthlyTotals($date) {
        $bills = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->whereDate('time_pickup_scheduled', '>=', $date)
            ->select(
                DB::raw('sum(price) as income'),
                DB::raw('date_format(time_pickup_scheduled, "%Y-%m") as month'),
                DB::raw('sum(interliner_cost) as interliner_cost'),
                // DB::raw('sum(interliner_cost_to_customer) as interliner_cost_to_customer')
            )->groupBy('month');

        return $bills->get();
    }

    public function GetPrepaidAccountsReceivable($startDate, $endDate) {
        $bills = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->leftjoin('payment_types', 'payment_types.payment_type_id', '=', 'charges.charge_type_id')
            ->where('payment_types.is_prepaid', 1)
            ->where('time_pickup_scheduled', '>=', $startDate->format('Y-m-01'))
            ->where('time_pickup_scheduled', '<=', $endDate->format('Y-m-t'))
            ->select(
                'bills.payment_type_id',
                'payment_types.name as payment_type_name',
                DB::raw('sum(case when price is null then 0 else price end) as amount')
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

        $bills = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->whereDate('time_pickup_scheduled', '>=', $date)
            ->whereIn('charge_type_id', $prepaidOptionIds)
            ->select(
                DB::raw('sum(price) as prepaid_income'),
                DB::raw('date_format(time_pickup_scheduled, "%Y-%m") as month')
            )->groupBy('month');

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
        $new = new Bill;

        $new = $new->create($bill);

        return $new;
    }

	public function ListAll($req) {
        $bills = Bill::leftJoin('charges', 'charges.bill_id', '=', 'bills.bill_id')
            ->leftJoin('line_items', 'line_items.charge_id', '=', 'charges.charge_id')
            ->leftJoin('addresses as delivery_address', 'delivery_address.address_id', '=', 'bills.delivery_address_id')
            ->leftJoin('addresses as pickup_address', 'pickup_address.address_id', '=', 'bills.pickup_address_id')
            ->leftJoin('interliners', 'interliners.interliner_id', '=', 'bills.interliner_id')
            ->leftJoin('employees as pickup_employee', 'pickup_employee.employee_id', '=', 'bills.pickup_driver_id')
            ->leftJoin('employees as delivery_employee', 'delivery_employee.employee_id', '=', 'bills.pickup_driver_id')
            ->leftJoin('contacts as pickup_employee_contact', 'pickup_employee.contact_id', '=', 'pickup_employee_contact.contact_id')
            ->leftJoin('contacts as delivery_employee_contact', 'delivery_employee.contact_id', '=', 'delivery_employee_contact.contact_id')
            ->leftJoin('selections as deliveryType', 'deliveryType.value', '=', 'bills.delivery_type')
            ->leftJoin('selections as repeatInterval', 'repeatInterval.selection_id', '=', 'bills.repeat_interval')
            ->leftJoin('accounts as charge_account', 'charges.charge_account_id', '=', 'charge_account.account_id')
            ->select(
                array_merge([
                    DB::raw('sum(price) as price'),
                    'bills.bill_id',
                    'bill_number',
                    'charge_account.account_id as charge_account_id',
                    'charges.charge_type_id as charge_type_id',
                    DB::raw('MIN(case when invoice_id is not null then 0 when pickup_manifest_id is not null then 0 when delivery_manifest_id is not null then 0 when paid is true then 0 else 1 end) as deletable'),
                    'delivery_address.formatted as delivery_address_formatted',
                    'delivery_address.name as delivery_address_name',
                    'deliveryType.name as type',
                    'description',
                    DB::raw('MIN(case when line_items.paid is null then 0 else paid end) as paid'),
                    'parent_account_id',
                    'percentage_complete',
                    'pickup_address.formatted as pickup_address_formatted',
                    'pickup_address.name as pickup_address_name',
                    'time_pickup_scheduled',
                    'time_delivery_scheduled',
                    'time_picked_up',
                    'time_delivered'
                ],
                $req->user()->can('viewDispatch', Bill::class) ? [
                    'delivery_driver_id',
                    DB::raw('concat(delivery_employee_contact.first_name, " ", delivery_employee_contact.last_name) as delivery_employee_name'),
                    'pickup_driver_id',
                    DB::raw('concat(pickup_employee_contact.first_name, " ", pickup_employee_contact.last_name) as pickup_employee_name'),
                ] : [],
                $req->user()->can('viewBilling', Bill::class) ? [
                    // 'delivery_address.lat as delivery_address_lat',
                    // 'delivery_address.lng as delivery_address_lng',
                    'interliners.interliner_id',
                    'interliners.name as interliner_name',
                    // 'pickup_address.lat as pickup_address_lat',
                    // 'pickup_address.lng as pickup_address_lng',
                    'repeat_interval',
                    'repeatInterval.name as repeat_interval_name',
                ] : []
            )
        );

        if($this->myAccounts) {
            $bills->whereIn('charges.charge_account_id', $this->myAccounts);
        }
        else if($this->employeeId && Auth::user()->cannot('viewAll', Bill::class)) {
            $bills->where('pickup_driver_id', $this->employeeId)
                ->orWhere('delivery_driver_id', $this->employeeId);
        }

        $filteredBills = QueryBuilder::for($bills)
            ->allowedFilters([
                AllowedFilter::custom('price', new NumberBetween),
                'bill_number',
                AllowedFilter::exact('charge_account_id', 'charge_account.account_id'),
                AllowedFilter::custom('dispatch', new Dispatch),
                AllowedFilter::exact('delivery_driver_id'),
                AllowedFilter::exact('interliner_id', 'bills.interliner_id'),
                AllowedFilter::exact('invoice_id', 'charges.lineItems.invoice_id'),
                AllowedFilter::exact('paid', 'line_items.paid'),
                AllowedFilter::exact('parent_account_id', 'charge_account.parent_account_id'),
                AllowedFilter::exact('skip_invoicing'),
                AllowedFilter::custom('time_pickup_scheduled', new DateBetween),
                AllowedFilter::custom('time_delivery_scheduled', new DateBetween),
                AllowedFilter::exact('charge_type_id', 'charges.charge_type_id'),
                AllowedFilter::custom('percentage_complete', new NumberBetween),
                AllowedFilter::exact('pickup_driver_id'),
                AllowedFilter::exact('repeat_interval')
            ]);

        return $filteredBills->groupBy('bill_id')->limit(5001)->get();
    }

    public function SetBillPickupOrDeliveryTime($billId, $type, $time) {
        $bill = $this->GetById($billId);

        if($type === 'pickup')
            $bill->time_picked_up = $time;
        else if ($type === 'delivery')
            $bill->time_delivered = $time;

        $bill->save();

        return $bill;
    }

    public function Update($bill, $permissions) {
        $lineItemRepo = new LineItemRepo();
        $lineItems = $lineItemRepo->GetByBillId($bill['bill_id']);

        $old = $this->GetById($bill['bill_id']);

        if($permissions['editBasic'])
            foreach(Bill::$basicFields as $field)
                $old->$field = $bill[$field];

        if($permissions['editDispatch'])
            foreach(Bill::$dispatchFields as $field) {
                if(($field === 'pickup_driver_id' || $field === 'pickup_driver_commission') && !$this->IsPickupDriverEditable($old['bill_id']))
                    continue;
                if(($field === 'delivery_driver_id' || $field === 'delivery_driver_commission') && !$this->IsDeliveryDriverEditable($old['bill_id']))
                    continue;
                $old->$field = $bill[$field];
            }


        if($permissions['editBilling'])
            foreach(Bill::$billingFields as $field)
                $old->$field = $bill[$field];

        $old->save();

        return $old;
    }

    /**
     * Private functions
     */

    /**
     * Checks the completion level of the bill after each insert/update
     * Triggered by laravel events
     * Must be done post update as a separate database write to properly check charges and line items (who would otherwise not yet be entered as they rely on a valid bill_id)
     * @param $billId - the valid id of a bill
     * 
     */
    public function CheckRequiredFields($billId) {
        $requiredFieldsHumanReadable = [
            'bill_number' => 'Bill number required',
            'delivery_driver_commission' => 'Delivery driver commission required',
            'delivery_driver_id' => 'Please select a delivery driver',
            'delivery_type' => 'Please select a delivery type',
            'pickup_driver_id' => 'Please select a pickup driver',
            'pickup_driver_commission' => 'Please enter pickup driver commission',
            'time_pickup_scheduled' => 'Please enter the approximate pickup time',
            'time_call_received' => 'Please enter the time the call was received',
            'time_delivery_scheduled' => 'Please enter the approximate delivery time',
            'time_dispatched' => 'Please enter the time the call was dispatched',
        ];

        $accountRepo = new AccountRepo();
        $chargeRepo = new ChargeRepo();
        $contactRepo = new ContactRepo();
        $lineItemRepo = new LineItemRepo();
        $paymentRepo = new PaymentRepo();

        $bill = $this->GetById($billId);

        $incompleteFields = [];
        $requiredFields = [
			'bill_number',
			'delivery_driver_commission',
			'delivery_driver_id',
			'delivery_type',
			'pickup_driver_id',
			'pickup_driver_commission',
			'time_pickup_scheduled',
			'time_delivery_scheduled',
			'time_call_received',
            'time_dispatched'
		];

        $charges = $chargeRepo->GetByBillId($bill->bill_id);
        if(!$charges || count($charges) === 0) {
            $incompleteFields[] = 'Must contain at least one charge';
            $incompleteFields[] = 'Must contain at least one line item';
        } else
            foreach($charges as $charge) {
                $paymentType = $paymentRepo->GetPaymentType($charge->charge_type_id);
                if($charge->account_id) {
                    $account = $accountRepo->GetById($charge->account_id);
                    if($account->is_custom_field_mandatory && !$charge['charge_reference_value'])
                        $requiredFields[] = $account->custom_field;
                }
                $lineItems = $lineItemRepo->GetByChargeId($charge->charge_id);
                if(count($lineItems) === 0) {
                    $name = $charge->type;
                    if($name === 'Account')
                        $name = $charge->charge_account_name;
                    else if($name === 'Employee')
                        $name = $charge->charge_employee_name;
                    $requiredFields[] = 'Charge to ' . $name . ' requires at least one line item';
                }
                else
                    foreach($lineItems as $lineItem) {
                        if($lineItem['name'] === 'Interliner' && !in_array('interliner_id', $requiredFields))
                            $requiredFields = array_merge($requiredFields, ['interliner_id', 'interliner_reference_value']);
                        if(!isset($lineItem->price))
                            $requiredFields[] = $lineItem->charge_id . '.' . $lineItem->name . '.price';
                        if(!isset($lineItem->driver_amount))
                            $requiredFields[] = $lineItem->charge_id . '.' . $lineItem->name . '.driver_amount';
                        if($lineItem->price == 0 && $lineItem->driver_amount == 0)
                            $requiredFields[] = 'Both price and driver amount cannot be zero on ' . $lineItem->name;
                        if($paymentType->is_prepaid && !$lineItem['paid'])
                            $requiredFields[] = $lineItem->charge_id . '.' . $lineItem->name . '.paid';
                    }
            }

        foreach($requiredFields as $field) {
            if(empty($bill->$field))
                array_push($incompleteFields, isset($requiredFieldsHumanReadable[$field]) ? $requiredFieldsHumanReadable[$field] : $field);
        }

        $percentageComplete = (int)((count($requiredFields) - count($incompleteFields)) / count($requiredFields) * 100);

        $bill->percentage_complete = $percentageComplete;
        $bill->incomplete_fields = $incompleteFields;

        $bill->save(['timestamps' => false]);
    }

    private function IsDeliveryDriverEditable($billId) {
        $lineItemRepo = new LineItemRepo();
        $lineItems = $lineItemRepo->GetByBillId($billId);

        foreach($lineItems as $lineItem)
            if($lineItem->delivery_manifest_id)
                return false;

        return true;
    }

    private function IsPickupDriverEditable($billId) {
        $lineItemRepo = new LineItemRepo();

        $lineItems = $lineItemRepo->GetByBillId($billId);

        foreach($lineItems as $lineItem)
            if($lineItem->pickup_manifest_id)
                return false;

        return true;
    }
}
