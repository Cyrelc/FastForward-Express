<?php
$factory->define(App\Package::class, function (Faker\Generator $faker) {
    $density = rand(250, 8000); //kg/m^3
    $height = rand(100, 10000) / 1000; //m
    $width = rand(100, 10000) / 1000; //m
    $length = rand(100, 10000) / 1000; //m

    return [
        //Units in m
        "height" => $height,
        "width" => $width,
        "length" => $length,
        "weight" => $density * $height * $width * $length  // d = m/v, m = dv
    ];
});
