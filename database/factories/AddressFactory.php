<?php

$factory->define(App\Address::class, function (Faker\Generator $faker) {
    $r = rand(0, 100);
    $province = '';
    $p1 = 0;
    $city = '';
    $s = rand(0, 10);

    if ($r > 62) {
        $province = "Ontario";
        //Ontario postals start with KLMNP
        $p1 = 79;
        while($p1==79)
            $p1 = rand(75, 80);

        if ($s < 6)
            $city = 'Toronto';
        else if ($s < 9)
            $city = 'Ottowa';
        else
            $city = 'Mississauga';
    }
    else if ($r > 39) {
        $province = "Québec";
        //Quebec postals start with GHJ
        $p1 = 73;
        while($p1==73)
            $p1 = rand(71, 74);

        if($s < 5)
            $city = 'Montreal';
        else if ($s < 9)
            $city = 'Québec City';
        else
            $city = 'Laval';
    }
    else if ($r > 26) {
        $province = "British Columbia";
        $p1 = 86;

        if($s < 5)
            $city = 'Vancouver';
        else if ($s < 9)
            $city = 'Surrey';
        else
            $city = 'Burnaby';
    }
    else if ($r > 16) {
        $province = "Alberta";
        $p1 = 84;

        $c = rand(0, 20);

        if($c < 5)
            $city = 'Edmonton';
        else if ($c < 9)
            $city = 'Calgary';
        else if ($c < 12)
            $city = 'Red Deer';
        else if ($c < 14)
            $city = 'St. Albert';
        else if ($c < 16)
            $city = 'Spruce Grove';
        else if ($c < 18)
            $city = 'Sherwood Park';
        else if ($c < 20)
            $city = 'Fort Saskatchewan';
    }
    else if ($r > 13) {
        $province = "Saskatchewan";
        $p1 = 83;

        if($s < 5)
            $city = 'Saskatoon';
        else if ($s < 9)
            $city = 'Regina';
        else
            $city = 'Prince Albert';
    }
    else if ($r > 10) {
        $province = "Manitoba";
        $p1 = 82;

        if($s < 5)
            $city = 'Winnipeg';
        else if ($s < 9)
            $city = 'Brandon';
        else
            $city = 'Steinbach';
    }
    else if ($r > 7) {
        $province = "Nova Scotia";
        $p1 = 66;

        if($s < 5)
            $city = 'Halifax';
        else if ($s < 9)
            $city = 'Cape Breton';
        else
            $city = 'Kings';
    }
    else if ($r > 5) {
        $province = "New Brunswick";
        $p1 = 69;

        if($s < 5)
            $city = 'Moncton';
        else if ($s < 9)
            $city = 'Saint John';
        else
            $city = 'Fredericton';
    }
    else if ($r > 3) {
        $province = "Newfoundland";
        $p1 = 65;

        if($s < 5)
            $city = 'St. John\'s';
        else if ($s < 9)
            $city = 'Conception Bay South';
        else
            $city = 'Mount Pearl';
    }
    else {
        $r = rand(0, 3);

        if ($r == 0) {
            $province = "Prince Edward Island";
            $p1 = 67;

            if($s < 5)
                $city = 'Charlottetown';
            else if ($s < 9)
                $city = 'Summerside';
            else
                $city = 'Stratford';
        }
        else if ($r == 1) {
            $province = "Northwest Territories";
            $p1 = 88;

            if($s < 5)
                $city = 'Yellowknife';
            else if ($s < 9)
                $city = 'Hay River';
            else
                $city = 'Inuvik';
        }
        else if ($r == 2) {
            $province = "Yukon Territories";
            $p1 = 89;

            if($s < 5)
                $city = 'Whitehorse';
            else if ($s < 9)
                $city = 'Dawson';
            else
                $city = 'Watson Lake';
        }
        else if ($r == 3) {
            $province = "Nunavut";
            $p1 = 88;

            if($s < 5)
                $city = 'Iqaluit';
            else if ($s < 9)
                $city = 'Arviat';
            else
                $city = 'Rankin Inlet';
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
        'city' => $city,
        'zip_postal' => $postal,
        'state_province' => $province,
        'country' => "Canada"
    ];
});
