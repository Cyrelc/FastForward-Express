<?php
$factory->define(App\InvoiceOption::class, function (Faker\Generator $faker) {
    $invoiceChance = rand(0, 4);
    $invoiceOption;

    switch($invoiceChance) {
        case 0:
            $invoiceOption = [
                "name" => "Location Sort",
                "field" => "account_id",
                "subtotal" => true,
                "priority" => 1
            ];
            break;

        case 1:
            $invoiceOption = [
                "name" => "Date Subsort",
                "field" => "date",
                "subtotal" => true,
                "priority" => 1
            ];
            break;

        case 2:
            $invoiceOption = [
                "name" => "Bill Number Subsort",
                "field" => "bill_number",
                "subtotal" => false,
                "priority" => 1
            ];
            break;

        case 3:
            $invoiceOption = [
                "name" => "Bill Text Subsort",
                "field" => "description",
                "subtotal" => false,
                "priority" => 1
            ];
            break;

        case 4:
            $invoiceOption = [
                "name" => "Custom Field Subsort",
                "field" => "custom_field_value",
                "subtotal" => false,
                "priority" => 1
            ];
            break;
    }

    return $invoiceOption;
});
