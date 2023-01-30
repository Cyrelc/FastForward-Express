<?php

namespace App\Console\Commands;

use App\Events\BillCreated;
use App\Http\Repos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Console\Command;

class GenerateRepeatingBills extends Command {
    protected $description = 'Generates repeating bills for the day';
    protected $signature = 'bills:generate_repeating';

    public function handle() {
        // We have to authenticate as some user here in order to have bill permissions
        // A service account should be created with the necessary permissions
        // Notably, this must be done **before** creation of the billRepo, which relies on user permissions for functionality
        $userRepo = new Repos\UserRepo();
        $user = $userRepo->GetUserByPrimaryEmail('system.scheduled.tasks');
        Auth::loginUsingId($user->user_id);

        $addressRepo = new Repos\AddressRepo();
        $appSettingsRepo = new Repos\ApplicationSettingsRepo();
        $billRepo = new Repos\BillRepo();

        $today = (new \DateTime())->setTime(12, 0, 0, 0);
        $blockedDates = $appSettingsRepo->GetByType('blocked_date');
        foreach($blockedDates as $date) {
            $blockedDate = (new \DateTime($date->value))->setTime(12, 0, 0, 0);
            if($blockedDate == $today) {
                activity('system_scheduler')->log('Skipped recurring bills due to matching holiday found: ' . $date->name);
                return;
            }
        }

        activity('system_scheduler')->log('Running daily and weekly recurring bills');
        $repeatingBills = $billRepo->GetRepeatingBillsForToday();
        if(!$repeatingBills) {
            activity('system_scheduler')->log('No repeating bills found for today');
            return;
        }

        foreach($repeatingBills as $repeatingBill) {
            // handle bill
            activity('system_scheduler')->log('attempting to copy bill ' . $repeatingBill->bill_id);
            $bill = $billRepo->CopyBill($user, $repeatingBill->bill_id);
            event(new BillCreated($bill));
        }
    }
}
