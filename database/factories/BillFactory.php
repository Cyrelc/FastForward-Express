<?php
$factory->define(App\Bill::class, function (Faker\Generator $faker) {

    $descriptions = [
        "5Kg Package",
        "10Kg Package",
        "15Kg Package",
        "20Kg Package",
    ];
    $amount = rand(10000, 500000)/100;

    $result = [
            "pickup_driver_id" => rand(0, 3),
            "delivery_driver_id" => rand(0, 3),
            "description" => $descriptions[rand(0,3)],
            "date" => $faker->dateTimeThisMonth,
            "amount" => $amount,
            "reference_id" => DB::table("references")->insertGetId([
                "reference_type_id" => rand(1, 4),
                "reference_value" => "1A2B3C4D5E6F"
            ])
        ];


    if (rand(0,2) == 0) {
        $result["interliner_id"] = rand(1, 10);
        $result["interliner_amount"] = $amount * (rand(0, 3) / 100);
    }
    
    return $result;
});
