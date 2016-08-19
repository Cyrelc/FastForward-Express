<?php
$factory->define(App\Invoice::class, function (Faker\Generator $faker) {
    $isPrinted = false;

    if (rand(0,1) == 1)
        $isPrinted = true;
    
    return [
        "account_id" => rand(1, 3),
        "date" => $faker->dateTimeThisMonth,
        "is_printed" => $isPrinted,
        "print_date" => $faker->dateTimeThisMonth,
        "zone" => rand(0,36),
        "comment" => $faker->sentence
    ];
});
