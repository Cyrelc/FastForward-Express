<?php

namespace App\Http\Models\Bill;

use App\Http\Models;
use App\Http\Repos;
use Illuminate\Support\Facades\Log;
use JsonSerializable;

class LineItemModel implements JsonSerializable {
    private $driverAmount;
    private $name;
    private $paid;
    private $price;
    private $type;

    public function __construct($name, $type, $price, $driverAmount = null, $paid = false) {
        $this->name = $name;
        $this->type = $type;
        $this->price = $price;
        $this->driverAmount = $driverAmount ? $driverAmount : $price;
        $this->paid = $paid;
    }

    public function getPrice() {
        return $this->price;
    }

    public function jsonSerialize() {
        return array (
            'name' => $this->name,
            'type' => $this->type,
            'price' => $this->price,
            'driver_amount' => $this->driverAmount,
        );
    }
}
?>
