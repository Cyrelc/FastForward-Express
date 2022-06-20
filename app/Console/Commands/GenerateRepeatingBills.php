<?php

namespace App\Console\Commands;

use App\Http\Repos;
use Illuminate\Support\Facades\Auth;

class GenerateRepeatingBills {
    public function __invoke() {
        // We have to authenticate as some user here in order to have bill permissions
        // A service account should be created with the necessary permissions
        // Notably, this must be done **before** creation of the billRepo, which relies on user permissions for functionality
        $userRepo = new Repos\UserRepo();
        $user = $userRepo->GetUserByPrimaryEmail('system.scheduled.tasks');
        Auth::loginUsingId($user->user_id);

        $addressRepo = new Repos\AddressRepo();
        $billRepo = new Repos\BillRepo();

        activity('system_debug')->log('Running daily and weekly recurring bills');
        $repeatingBills = $billRepo->GetRepeatingBillsForToday();
        if(!$repeatingBills)
            return;
        foreach($repeatingBills as $repeatingBill) {
            // handle bill
            $bill = $billRepo->CopyBill($user, $repeatingBill->bill_id);
            event(BillCreated($bill));
        }
    }
}
