<?php
namespace App\Http\Repos;

use App\Charge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChargeRepo {
    private $myAccounts;
    private $employeeId;

    function __construct() {
        $user = Auth::user();
        $accountRepo = new AccountRepo();

        $this->myAccounts = $user->accountUsers ? $accountRepo->GetMyAccountIds($user, $user->can('bills.view.basic.children')) : null;
        $this->employeeId = $user->employee ? $user->employee->employee_id : null;
    }

    public function Delete($chargeId) {
        $lineItemRepo = new LineItemRepo();
        $lineItems = $lineItemRepo->GetByChargeId($chargeId);

        $charge = Charge::where('charge_id', $chargeId)->first();
        foreach($lineItems as $lineItem)
            $lineItemRepo->Delete($lineItem->line_item_id);
        $charge->delete();
        return;
    }

    public function DeleteByBillId($billId) {
        $charges = $this->GetByBillId($billId);

        foreach($charges as $charge)
            $this->Delete($charge->charge_id);
    }

    public function GetByBillId($billId) {
        $charges = Charge::where('bill_id', $billId)
            ->leftJoin('line_items', 'line_items.charge_id', '=', 'charges.charge_id')
            ->leftJoin('payment_types', 'payment_types.payment_type_id', '=', 'charges.charge_type_id')
            ->leftJoin('accounts', 'accounts.account_id', '=', 'charges.charge_account_id')
            ->leftJoin('employees', 'employees.employee_id', '=', 'charges.charge_employee_id')
            ->leftJoin('contacts', 'contacts.contact_id', '=', 'employees.contact_id')
            ->select(array_merge([
                'charges.charge_id',
                DB::raw('SUM(line_items.price) as price'),
                DB::raw('case when charges.charge_account_id is not null then accounts.custom_field when charges.charge_employee_id is null then payment_types.required_field end as charge_reference_value_label'),
                DB::raw('case when charges.charge_account_id is not null then accounts.is_custom_field_mandatory when charges.charge_employee_id is null then payment_types.required_field is null end as charge_reference_value_required'),
                'accounts.account_id',
                'accounts.account_number as charge_account_number',
                'accounts.name as charge_account_name',
                'charge_account_id',
                'charge_reference_value',
                'charge_type_id',
                'payment_types.name as type',
                DB::raw('concat(contacts.first_name, " ", contacts.last_name) as charge_employee_name'),
                DB::raw('case when charges.charge_account_id is not null then concat(accounts.account_number, " - ", accounts.name) when charges.charge_employee_id is not null then concat(contacts.first_name, " ", contacts.last_name) else payment_types.name end as name')
            ],
            $this->employeeId ? [
                DB::raw('SUM(line_items.driver_amount) as driver_amount'),
                'charges.charge_employee_id',
            ] : []
        ));

        if($this->myAccounts)
            $charges->whereIn('charges.charge_account_id', $this->myAccounts);

        return $charges->groupBy('charges.charge_id')->get();
    }

    public function GetById($chargeId) {
        $charge = Charge::where('charge_id', $chargeId);

        return $charge->first();
    }

    public function GetByInvoiceId($invoiceId) {
        $charges = Charge::leftJoin('line_items', 'charges.charge_id', '=', 'line_items.charge_id')
            ->where('line_items.invoice_id', $invoiceId)
            ->groupBy('charges.charge_id');

        return $charges->get();
    }

    public function GetWithUninvoicedPrepaid($prepaidTypes, $startDate, $endDate) {
        $charges = Charge::leftJoin('line_items', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->leftJoin('payment_types', 'payment_types.payment_type_id', '=', 'charges.charge_type_id')
            ->whereIn('charge_type_id', $prepaidTypes)
            ->whereNull('invoice_id')
            ->select(
                'bills.bill_id',
                'charges.charge_id',
                DB::raw('SUM(line_items.price) as price'),
                'payment_types.name as parent_account',
                'bills.bill_id as id',
                'bills.bill_number as number',
                DB::raw('case when percentage_complete = 100 and skip_invoicing = false and cast(time_pickup_scheduled as date) between cast("' . $startDate . '" as date) and cast("' . $endDate . '" as date) then 1 else 0 end as valid_bill_count'),
                DB::raw('case when percentage_complete = 100 and skip_invoicing = true then 1 else 0 end as skipped_bill_count'),
                DB::raw('case when percentage_complete < 100 then 1 else 0 end as incomplete_bill_count'),
                DB::raw('case when percentage_complete = 100 and skip_invoicing = false and cast(time_pickup_scheduled as date) < cast("' . $startDate . '" as date) then 1 else 0 end as legacy_bill_count'),
                DB::raw('"prepaid" as type'),
                DB::raw('cast(time_pickup_scheduled as date) as time_pickup_scheduled')
            )->groupBy('charges.charge_id');

        return $charges->get();
    }

    public function Insert($charge) {
        $new = new Charge;

        return $new->create($charge);
    }

    public function Update($charge) {
        $old = Charge::where('charge_id', $charge['charge_id'])->first();
        $old->charge_reference_value = $charge['charge_reference_value'];

        $old->save();
        return $old;
    }
}
