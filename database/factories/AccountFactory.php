<?php
$factory->define(App\Account::class, function (Faker\Generator $faker) {
    $sendBills = false;

    if (rand(0,1) == 0)
        $sendBills = true;

    $customField = rand(0, 10);

    $intervalChance = rand(0, 2);
    $interval;

    switch($intervalChance) {
        case 0:
            $interval = 'monthly';
            break;

        case 1:
            $interval = 'bi-weekly';
            break;

        case 2:
            $interval = 'weekly';
            break;
    }

    return [
        "rate_type_id" => rand(1,2),
        "account_number" => Carbon\Carbon::now()->timestamp,
        "invoice_interval" => $interval,
        "name" => $faker->company,
        "start_date" => $faker->dateTimeThisYear,
        "send_bills" => $sendBills,
        "gst_exempt" => rand(0,10) == 1 ? false : true,
        "charge_interest" => rand(0,10) == 1 ? false : true,
        "can_be_parent" => rand(0,3) == 1 ? true : false,
        "uses_custom_field" => $customField == 1 ? true : false,
        "custom_field" => $customField == 1 ? $faker->text(8) : null
    ];
});
