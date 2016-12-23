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
        "send_bills" => $sendBills
    ];
});
