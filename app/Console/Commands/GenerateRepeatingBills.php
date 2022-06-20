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
            // handle addresses
            $pickupAddress = $this->makeNewAddress($repeatingBill->pickup_address_id);
            $deliveryAddress = $this->makeNewAddress($repeatingBill->delivery_address_id);
            $pickupAddressId = $addressRepo->InsertMinimal($pickupAddress)->address_id;
            $deliveryAddressId = $addressRepo->InsertMinimal($deliveryAddress)->address_id;
            // handle bill
            $bill = $this->makeNewBill($repeatingBill, $pickupAddressId, $deliveryAddressId);
            $billRepo->Insert($bill);
        }
    }

    private function makeNewAddress($addressId) {
        $addressRepo = new Repos\AddressRepo();
        $address = $addressRepo->GetById($addressId);
        return [
            'name' => $address->name,
            'is_primary' => false,
            'contact_id' => null,
            'lat' => $address->lat,
            'lng' => $address->lng,
            'formatted' => $address->formatted,
            'place_id' => $address->place_id
        ];
    }

    private function makeNewBill($oldBill, $pickupAddressId, $deliveryAddressId) {
        // handle new times
        $pickupTime = new \DateTime();
        $deliveryTime = new \DateTime();
        $originalPickupTime = new \DateTime($oldBill->time_pickup_scheduled);
        $originalDeliveryTime = new \DateTime($oldBill->time_delivery_scheduled);
        // if($repeatIntervalString === 'weekly') {
        //     $pickupTime = new \DateTime('next ' . $originalPickupTime->format('l'));
        //     $deliveryTime = new \DateTime('next ' . $originalDeliveryTime->format('l'));
        // }
        $pickupTime->setTime($originalPickupTime->format('H'), $originalPickupTime->format('i'));
        $deliveryTime->setTime($originalDeliveryTime->format('H'), $originalDeliveryTime->format('i'));
        activity('time-debug')->log($pickupTime->format('Y-m-d H:i:s'));
        activity('time-debug')->log($deliveryTime->format('Y-m-d H:i:s'));
        return [
            'amount' => $oldBill->amount,
            'bill_number' => null,
            'charge_account_id' => $oldBill->charge_account_id,
            'charge_reference_value' => $oldBill->charge_reference_value,
            'description' => $oldBill->description,
            'delivery_account_id' => $oldBill->delivery_account_id,
            'delivery_address_id' => $deliveryAddressId,
            'delivery_driver_commission' => $oldBill->delivery_driver_commission,
            'delivery_driver_id' => $oldBill->delivery_driver_id,
            'delivery_reference_value' => $oldBill->delivery_reference_value,
            'delivery_type' => $oldBill->delivery_type,
            'interliner_id' => $oldBill->interliner_id,
            'interliner_cost' => $oldBill->interliner_cost,
            'interliner_cost_to_customer' => $oldBill->interliner_cost_to_customer,
            // skipping as this does not make sense
            // 'interliner_reference_value' => $oldBill->interliner_reference_value,
            'is_min_weight_size' => $oldBill->is_min_weight_size,
            'is_pallet' => $oldBill->is_pallet,
            'packages' => $oldBill->packages,
            'payment_id' => $oldBill->payment_id,
            'payment_type_id' => $oldBill->payment_type_id,
            'pickup_account_id' => $oldBill->pickup_account_id,
            'pickup_address_id' => $pickupAddressId,
            'pickup_reference_value' => $oldBill->pickup_reference_value,
            'pickup_driver_id' => $oldBill->pickup_driver_id,
            'pickup_driver_commission' => $oldBill->pickup_driver_commission,
            'repeat_interval' => null,
            'skip_invoicing' => $oldBill->skip_invoicing,
            'time_pickup_scheduled' => $pickupTime,
            'time_delivery_scheduled' => $deliveryTime,
            'time_call_received' => new \DateTime(),
            'time_dispatched' => ($oldBill->pickup_driver_id && $oldBill->delivery_driver_id) ? new \DateTime() : null,
            'use_imperial' => $oldBill->use_imperial
        ];
    }
}
