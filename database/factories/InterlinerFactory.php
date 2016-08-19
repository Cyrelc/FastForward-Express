<?php
$factory->define(App\Interliner::class, function (Faker\Generator $faker) {
    return [
        "name" => $faker->company,
        "address_id" => factory(App\Address::class)->create()->address_id
    ];
});
