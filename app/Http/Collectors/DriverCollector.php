<?php

namespace App\Http\Collectors;
use \App\Http\Validation\Utils;

class DriverCollector {
    public function Collect($req, $employee_id) {
        return [
            'driver_id' => $req->input('driver_id'),
            'employee_id' => $employee_id,
            'company_name' => $req->company_name == '' ? null : $req->company_name,
            'drivers_license_number' => $req->input('DLN'),
            'license_expiration' => (new \DateTime($req->input('license_expiration')))->format('Y-m-d'),
            'license_plate_number' => $req->input('license_plate'),
            'license_plate_expiration' => (new \DateTime($req->input('license_plate_expiration')))->format('Y-m-d'),
            'insurance_number' => $req->input('insurance'),
            'insurance_expiration' => (new \DateTime($req->input('insurance_expiration')))->format('Y-m-d'),
            'pickup_commission' => $req->input('pickup-commission'),
            'delivery_commission' => $req->input('delivery-commission'),
        ];
    }

    public function CollectPager($req, $contactId) {
        return [
            'phone_number_id' => $req->input('pager-id'),
            'type' => 'pager',
            'phone_number' => $req->input('pager'),
            'extension_number' => $req->input('pager-ext'),
            'is_primary' => false,
            'contact_id' => $contactId
        ];
    }

    public function Remerge($req, $driver) {
        if (Utils::HasValue($req->old('DLN')))
            $driver->drivers_license_number = $req->old('DLN');

        if (Utils::HasValue($req->old('license_expiration')))
            $driver->license_expiration = strtotime($req->old('license_expiration'));

        if (Utils::HasValue($req->old('license_plate')))
            $driver->license_plate_number = $req->old('license_plate');

        if (Utils::HasValue($req->old('insurance')))
            $driver->insurance_number = $req->old('insurance');

        if (Utils::HasValue($req->old('insurance_expiration')))
            $driver->insurance_expiration = strtotime($req->old('insurance_expiration'));

        if (Utils::HasValue($req->old('pickup-commission')))
            $driver->pickup_commission = $req->old('pickup-commission');

        if (Utils::HasValue($req->old('delivery-commission')))
            $driver->delivery_commission = $req->old('delivery-commission');

        return $driver;
    }
}
