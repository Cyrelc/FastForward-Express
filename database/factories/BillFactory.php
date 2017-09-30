<?php
$factory->define(App\Bill::class, function (Faker\Generator $faker) {

    $descriptions = [
        "5Kg Package",
        "10Kg Package",
        "15Kg Package",
        "20Kg Package",
    ];
    $amount = rand(10000, 500000)/100;

    $cash = rand(0, 4);
        $result = [
        "charge_account_id" => $cash == 0 ? null : rand(1, 40),
        "pickup_driver_id" => rand(1, 4),
        "delivery_driver_id" => rand(1, 4),
        "description" => $descriptions[rand(0,3)],
        "date" => $faker->dateTimeThisYear,
        "amount" => $amount
    ];

    $result["skip_invoicing"] = (rand(0, 3) == 0);

    if (rand(0,2) == 0) {
        $result["interliner_id"] = rand(1, 10);
        $result["interliner_amount"] = $amount * (rand(0, 3) / 100);
    }

    $result["delivery_type"] = $descriptions[rand(0, 3)];
    
    $hasDates = rand(0, 1) == 1;

    if ($hasDates) {
        $baseDate = $faker->dateTimeThisYear;
        $result["call_received"] = $baseDate;
        
        date('Y-m-d H:i:s', strtotime('+1 day', $baseDate->getTimestamp()));      
        $result["picked_up"] = $baseDate;

        date('Y-m-d H:i:s', strtotime('+1 day', $baseDate->getTimestamp()));
        $result["delivered"] = $baseDate;
    }

    $result["bill_number"] = round(microtime(true) * 1000);
    
    return $result;
});
