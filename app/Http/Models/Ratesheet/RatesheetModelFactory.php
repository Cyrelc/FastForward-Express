<?php
namespace App\Http\Models\Ratesheet;

use App\Http\Repos;
use App\Http\Models;
use App\Http\Models\Ratesheet;

class RatesheetModelFactory {
    public function ListAll() {
        $ratesheetRepo = new Repos\RatesheetRepo();
        return $ratesheetRepo->ListAll();
    }

    public function GetById($id) {
        return null;
    }

    public function GetCreateModel() {
        $selectionsRepo = new Repos\SelectionsRepo();
        $deliveryTypes = $selectionsRepo->GetSelectionsByType('delivery_type');
        $model = new RatesheetFormModel();
        $model->name = '';
        $model->deliveryTypes = array_map(array($this, 'PrepareDeliveryType'), $deliveryTypes->toArray());
        $model->timeRates = array(['name' => 'Evenings', 'price' => '', 'brackets' => array(['startTime' => '', 'endTime' => '', 'startDayOfWeek' => '', 'endDayOfWeek' => ''])],
                                ['name' => 'Evenings', 'price' => '', 'brackets' => array(['startTime' => '', 'endTime' => '', 'startDayOfWeek' => '', 'endDayOfWeek' => ''])]);
        $model->weightRates = array(['name' => 'Basic Weight Rate', 'brackets' => array(['lbmax' => '', 'kbmax' => '', 'additionalXKgs' => '', 'additionalXLbs' => '', 'price' => ''])],
                                    ['name' => 'Pallet Weight Rate', 'brackets' => array(['lbmax' => '', 'kbmax' => '', 'additionalXKgs' => '', 'additionalXLbs' => '', 'price' => ''])]);
        $model->zoneRates = array(['id' => 0, 'zones' => 1, 'regularCost' => '', 'rushCost' => '', 'directCost' => '', 'directRushCost' => '' ]);
        $mode->miscRates = array(['name' => '', 'price' => '']);
        $model->useInternalZonesCalc = false;
        return $model;
    }

    public function GetEditModel($ratesheetId) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $ratesheet = $ratesheetRepo->GetById($ratesheetId);
        $model = new RatesheetFormModel();
        $model->name = $ratesheet->name;
        $model->useInternalZonesCalc = filter_var($ratesheet->use_internal_zones_calc, FILTER_VALIDATE_BOOLEAN);
        $model->deliveryTypes = json_decode($ratesheet->delivery_types);
        $model->timeRates = json_decode($ratesheet->time_rates);
        $model->weightRates = json_decode($ratesheet->weight_rates);
        $model->zoneRates = json_decode($ratesheet->zone_rates);
        $model->mapZones = $ratesheetRepo->GetMapZones($ratesheetId);
        $model->miscRates = json_decode($ratesheet->misc_rates);
        $model->ratesheets = $ratesheetRepo->ListAll();
        foreach($model->mapZones as $mapZone)
            $mapZone->coordinates = json_decode($mapZone->coordinates);

        return $model;
    }

    public function ListForBillsPage($permissions) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        //TODO - Does not separate out based on account permissions, instead is just grabbing all available for all users
        if($permissions['createBasic'] || $permissions['editBasic'] || $permissions['createFull'])
            return $ratesheetRepo->ListForBillsPage();
    }

    static function PrepareDeliveryType($type, $new = true) {
        if($new)
            return [
                'friendlyName' => $type['name'],
                'id' => $type['value'],
                'time' => '',
                'cost' => ''
            ];
        return [
            'friendlyName' => $type['name'],
            'id' => $type['id'],
            'time' => $type['time'],
            'cost' => $type['cost']
        ];
    }
}

?>
