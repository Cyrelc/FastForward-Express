<?php

namespace App\Http\Resources;

use App\Models\Account;
use App\Models\Employee;
use App\Models\PaymentType;
use App\Models\Selection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ListResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $lists = [
            'accounts' => [],
            'email_types' => Selection::where('type', 'contact_type')->select('name as label', 'selection_id as value')->get(),
            'phone_types' => Selection::where('type', 'phone_type')->select('name as label', 'selection_id as value')->get(),
            'payment_types' => PaymentType::select('name as label', 'payment_type_id as value')->get(),
        ];

        $authUser = $this;

        if($authUser->employee || $authUser->hasRole('superAdmin')) {
            if($authUser->can('viewAll', Account::class) || $authUser->can('bills.view.basic'))
                $lists['accounts'] = Account::select(
                    DB::raw('concat(account_number, " - ", name) as label'),
                    'account_id as value',
                    'can_be_parent',
                )->get();
            if($authUser->can('viewAll', Account::class)) {
                $lists['invoice_intervals'] = Selection::where('type', 'invoice_interval')->select('name as label', 'selection_id as value')->get();
                $lists['parent_accounts'] = Account::where('can_be_parent', true)
                    ->select(
                        DB::raw('concat(account_number, " - ", name) as label'),
                        'account_id as value'
                    )->get();
            }
            if($authUser->can('viewAll', Employee::class) || $authUser->can('bills.view.dispatch.*')) {
                $lists['employees'] = Employee::all()->map(function ($employee) {
                    return [
                        'delivery_commission' => $employee->delivery_commission,
                        'is_driver' => $employee->is_driver,
                        'is_enabled' => $employee->user->is_enabled,
                        'label' => $employee->employee_number . ' - ' . $employee->contact->displayName(),
                        'pickup_commission' => $employee->pickup_commission,
                        'value' => $employee->employee_id,
                    ];
                });
                $lists['vehicle_types'] = Selection::where('type', 'vehicle_type')->select('name as label', 'selection_id as value')->get();
            }
            if($authUser->can('bills.edit.billing.*')) {
                $lists['repeat_intervals'] = Selection::where('type', 'repeat_interval')->select('name as label', 'selection_id as value')->get();
            }
        } else if(count($authUser->accountUsers) > 0 ) {
            $accountRepo = new \App\Http\Repos\AccountRepo();
            $lists['accounts'] = $accountRepo->List($authUser, $authUser->can('viewChildAccounts', $accountRepo->GetById($authUser->accountUsers[0]->account_id)));
        }

        return $lists;
    }
}
