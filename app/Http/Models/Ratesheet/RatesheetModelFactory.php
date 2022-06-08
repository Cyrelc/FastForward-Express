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
        $ratesheetRepo = new Repos\RatesheetRepo();
        $selectionsRepo = new Repos\SelectionsRepo();

        $model = new RatesheetFormModel();

        $deliveryTypes = $selectionsRepo->GetSelectionsByType('delivery_type');
        $model->ratesheets = $ratesheetRepo->ListAll();

        $model->name = '';
        $model->deliveryTypes = array_map(array($this, 'PrepareDeliveryType'), $deliveryTypes->toArray());
        $model->timeRates = array(['name' => 'Evenings', 'price' => '', 'brackets' => array(['startTime' => '', 'endTime' => '', 'startDayOfWeek' => '', 'endDayOfWeek' => ''])],
                                ['name' => 'Weekends', 'price' => '', 'brackets' => array(['startTime' => '', 'endTime' => '', 'startDayOfWeek' => '', 'endDayOfWeek' => ''])]);
        $model->weightRates = array(['name' => 'Basic Weight Rate', 'brackets' => array(['lbmax' => '', 'kbmax' => '', 'additionalXKgs' => '', 'additionalXLbs' => '', 'price' => ''])],
                                    ['name' => 'Pallet Weight Rate', 'brackets' => array(['lbmax' => '', 'kbmax' => '', 'additionalXKgs' => '', 'additionalXLbs' => '', 'price' => ''])]);
        $model->zoneRates = array(['id' => 0, 'zones' => 1, 'regularCost' => '', 'rushCost' => '', 'directCost' => '', 'directRushCost' => '' ]);
        $model->miscRates = array();
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
