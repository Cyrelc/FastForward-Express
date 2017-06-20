<?php

$factory->define(App\Address::class, function (Faker\Generator $faker) {
    $r = rand(0, 100);
    $province = '';
    $p1 = 0;

    if ($r > 62) {
        $province = "Ontario";
        //Ontario postals start with KLMNP
        $p1 = 79;
        while($p1==79)
            $p1 = rand(75, 80);
    }
    else if ($r > 39) {
        $province = "Quebec";
        //Quebec postals start with GHJ
        $p1 = 73;
        while($p1==73)
            $p1 = rand(71, 74);
    }
    else if ($r > 26) {
        $province = "British Columbia";
        $p1 = 86;
    }
    else if ($r > 16) {
        $province = "Alberta";
        $p1 = 84;
    }
    else if ($r > 13) {
        $province = "Saskatchewan";
        $p1 = 83;
    }
    else if ($r > 10) {
        $province = "Manitoba";
        $p1 = 82;
    }
    else if ($r > 7) {
        $province = "Nova Scotia";
        $p1 = 66;
    }
    else if ($r > 5) {
        $province = "New Brunswick";
        $p1 = 69;
    }
    else if ($r > 3) {
        $province = "Newfoundland";
        $p1 = 65;
    }
    else {
        $r = rand(0, 3);

        if ($r == 0) {
            $province = "Prince Edward Island";
            $p1 = 67;
        }
        else if ($r == 1) {
            $province = "Northwest Territories";
            $p1 = 88;
        }
        else if ($r == 2) {
            $province = "Yukon Territories";
            $p1 = 89;
        }
        else if ($r == 3) {
            $province = "Nunavut";
            $p1 = 88;
        }
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

    //Generate ASCII number for the last 2 alphabetic portions of postal code
    $p3 = rand(65, 90);
    $p5 = rand(65, 90);

    //Cdn postals cannot contain DFIOQUWZ in the first 2 alphabetic characters of the code
    while($p3 == 68 || $p3 == 70 || $p3 == 73 || $p3 == 79 || $p3 == 81 || $p3 == 85 || $p3 == 87 || $p3 == 90)
        $p3 = rand(65, 90);

    //The third alphabetic character may include W and Z
    while($p5 == 68 || $p5 == 70 || $p5 == 73 || $p5 == 79 || $p5 == 81 || $p5 == 85)
        $p5 = rand(65, 90);

    $postal = chr($p1) . rand(0,9) . chr($p3) . rand(0,9) . chr($p5) . rand(0,9);

    $street = rand(100, 250) . rand(10, 50);

    return [
        'street' => $street . ' ' . rand(1, 250) . $suffix,
        'street2' => "",
        'city' => $faker->city,
        'zip_postal' => $postal,
        'state_province' => $province,
        'country' => "Canada"
    ];
});
