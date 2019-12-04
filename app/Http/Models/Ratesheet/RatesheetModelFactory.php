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
        $model->holidayCharge = '';
        $model->weekendCharge = '';
        $model->deliveryTypes = array_map(array($this, 'PrepareDeliveryType'), $deliveryTypes->toArray());
        $model->palletRate = ['palletBasePrice' => '', 'palletBaseWeightKgs' => '', 'palletBaseWeightLbs' => '', 'palletAdditionalCharge' => '', 'palletAdditionalWeightKgs' => '', 'palletAdditionalWeightLbs' => ''];
        $model->timeRates = array(['id' => 0, 'startTime' => '', 'endTime' => '', 'cost' => ''],
                                ['id' => 1, 'startTime' => '', 'endTime' => '', 'cost' => ''],
                                ['id' => 2, 'startTime' => '', 'endTime' => '', 'cost' => '']);
        $model->weightRates = array(['id' => 0, 'lbmin' => 0, 'lbmax' => '', 'kgmin' => 0, 'kgmax' => '', 'cost' => '']);
        $model->zoneRates = array(['id' => 0, 'zones' => 1, 'regularCost' => '', 'rushCost' => '', 'directCost' => '', 'directRushCost' => '' ]);
        $model->useInternalZonesCalc = false;
        return $model;
    }

    public function GetEditModel($ratesheet_id) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $ratesheet = $ratesheetRepo->GetById($ratesheet_id);
        $model = new RatesheetFormModel();
        $model->name = $ratesheet->name;
        $model->holidayCharge = $ratesheet->holiday_charge;
        $model->weekendCharge = $ratesheet->weekend_charge;
        $model->useInternalZonesCalc = $ratesheet->use_internal_zones_calc;
        $model->deliveryTypes = json_decode($ratesheet->delivery_types);
        $model->palletRate = json_decode($ratesheet->pallet_rate);
        $model->timeRates = json_decode($ratesheet->time_rates);
        $model->weightRates = json_decode($ratesheet->weight_rates);
        $model->zoneRates = json_decode($ratesheet->zone_rates);
        $model->mapZones = json_decode($ratesheet->map_zones);

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
