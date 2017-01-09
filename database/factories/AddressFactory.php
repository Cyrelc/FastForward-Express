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

    $suffix = " St. ";

    if (rand(0,1) == 0) {
        $suffix = " Ave. ";
    }

    $direction = rand(0, 3);
    switch($direction) {
        case 0:
            $suffix = $suffix . 'NE';
            break;

        case 1:
            $suffix = $suffix . 'SE';
            break;

        case 2:
            $suffix = $suffix . 'SW';
            break;

        case 3:
            $suffix = $suffix . 'NW';
            break;
    }

    if (rand(0,4) == 3)
        $suffix . ' APT ' . rand(100, 420);

    return [
        'street' => rand(100, 99999) . rand(1, 250) . $suffix,
        'street2' => "",
        'city' => $faker->city,
        'zip_postal' => $faker->postcode,
        'state_province' => $province,
        'country' => "Canada"
    ];
});
