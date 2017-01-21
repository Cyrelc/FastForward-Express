<?php
$factory->define(App\Account::class, function (Faker\Generator $faker) {
    $sendBills = false;

    if (rand(0,1) == 0)
        $sendBills = true;

    return [
        "rate_type_id" => rand(1,2),
        "account_number" => Carbon\Carbon::now()->timestamp,
        "invoice_interval" => "monthly",
        "name" => $faker->company,
        "start_date" => $faker->dateTimeThisYear,
        "send_bills" => $sendBills,
        "gst_exempt" => rand(0,10) == 1 ? false : true,
        "charge_interest" => rand(0,10) == 1 ? false : true,
        "can_be_parent" => rand(0,3) == 1 ? true : false,
        "custom_field" => rand(0, 10) == 1 ? $faker->text(10) : null
    ];
});
