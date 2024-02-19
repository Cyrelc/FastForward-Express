<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase {
    use CreatesApplication;
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost/';

    protected $seed = true;

    protected function assertDoesNotHaveAttributes($array, $forbiddenAttributes) {
        $objectAttributes = array_keys((array) $array);
        // Check for forbidden attributes
        foreach ($forbiddenAttributes as $attribute) {
            $this->assertNotContains($attribute, $objectAttributes, "Forbidden attribute {$attribute} was found in the object.");
        }
    }

    protected function assertHasAttributes($array, $expectedAttributes) {
        $objectAttributes = array_keys((array) $array);

        // Check for expected attributes
        foreach ($expectedAttributes as $attribute) {
            $this->assertContains($attribute, $objectAttributes, "Attribute {$attribute} is missing from the object.");
        }
    }

    protected function assertHasOnlyAttributes($array, $expectedAttributes) {
        $objectAttributes = array_keys((array) $array);

        // Ensure no additional attributes are present
        $additionalAttributes = array_diff($objectAttributes, $expectedAttributes);
        $this->assertEmpty($additionalAttributes, "Additional attributes found in the object: " . implode(', ', $additionalAttributes));
    }

    protected function assertEmailAddressExists($emailAddress) {
        $this->assertDatabaseHas('email_addresses', array_merge($emailAddress->toArray(), ['type' => $this->castAsJson($emailAddress->type)]));
    }
}
