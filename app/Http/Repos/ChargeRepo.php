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
            ->select(
                'charges.charge_id',
                DB::raw('SUM(line_items.price) as price'),
                DB::raw('SUM(line_items.driver_amount) as driver_amount'),
                DB::raw('case when charges.charge_account_id is not null then accounts.custom_field when charges.charge_employee_id is null then payment_types.required_field end as charge_reference_value_label'),
                DB::raw('case when charges.charge_account_id is not null then accounts.is_custom_field_mandatory when charges.charge_employee_id is null then payment_types.required_field is null end as charge_reference_value_required'),
                'accounts.account_id',
                'accounts.name as charge_account_name',
                'charge_reference_value',
                'charge_type_id',
                'payment_types.name as type',
                'charges.charge_employee_id as employee_id',
                DB::raw('concat(contacts.first_name, " ", contacts.last_name) as charge_employee_name')
            );

        if($this->myAccounts)
            $charges->whereIn('charges.charge_account_id', $this->myAccounts);

        return $charges->groupBy('charges.charge_id')->get();
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
