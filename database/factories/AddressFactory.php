<?php

$factory->define(App\Address::class, function (Faker\Generator $faker) {
    $r = rand(0, 100);
    $province = '';

    if ($r > 62)
        $province = "Ontario";
    else if ($r > 39)
        $province = "Quebec";
    else if ($r > 26)
        $province = "British Columbia";
    else if ($r > 16)
        $province = "Alberta";
    else if ($r > 13)
        $province = "Saskatchewan";
    else if ($r > 10)
        $province = "Manitoba";
    else if ($r > 7)
        $province = "Nova Scotia";
    else if ($r > 5)
        $province = "New Brunswick";
    else if ($r > 3)
        $province = "Newfoundland";
    else {
        $r = rand(0, 3);

        if ($r == 0)
            $province = "Prince Edward Island";
        else if ($r == 1)
            $province = "Northwest Territories";
        else if ($r == 2)
            $province = "Yukon Territories";
        else if ($r == 3)
            $province = "Nunavut";
    }


    return [
        'street' => $faker->address,
        'street2' => "",
        'city' => $faker->city,
        'zip_postal' => $faker->postcode,
        'state_province' => $province,
        'country' => "Canada"
    ];
});
