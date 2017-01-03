<?php

$factory->define(App\Contact::class, function (Faker\Generator $faker) {
	$a = factory(App\Address::class)->create();
	
    return [
        'first_name' => $faker->firstname,
        'last_name' => $faker->lastName
    ];
});

