<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Contact;
use App\Models\EmailAddress;
use App\Models\PhoneNumber;
use App\Employee;
use App\Http\Controllers\EmployeeController;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase {
    use RefreshDatabase;

    protected function setUp() : void {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $response = $this->get('/api');
    }
    /**
     * create model requires select options from the database such as phone_types
     */
    public function testGetCreateModelForbidden() {
        $response = $this->get('/employees/create');

        $response->assertStatus(403);
    }

    public function testGetCreateModelSuccess() {
        $this->user->givePermissionTo('employees.create');
        $response = $this->get('/employees/create');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'contact' => [
                    'phone_types',
                ],
                'employee_permissions',
                'permissions',
                'vehicle_types'
            ]);
    }

    public function testCreateForbidden() {
        $data = ['data' => 'irrelevant'];
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->postJson('/employees', $data);

        $response->assertStatus(403);
    }

    public function testCreate() {
        $this->user->givePermissionTo('employees.create');

        $employee = Employee::factory()->make();
        $contact = Contact::factory()->make();
        $address = Address::factory()->make();
        $phoneNumbers = PhoneNumber::factory()->count(3)->make(['contact_id' => null]);
        $phoneNumbers[2]->is_primary = true;
        $emailAddresses = EmailAddress::factory()->count(3)->make(['contact_id' => null]);
        $emailAddresses[0]->is_primary = true;

        $employee->birth_date = $employee->dob;

        $data = array_merge(
            $employee->toArray(),
            ['contact' => array_merge(
                $contact->toArray(),
                [
                    'address' => $address->toArray(),
                    'email_addresses' => $emailAddresses->toArray(),
                    'phone_numbers' => $phoneNumbers->toArray()
                ]
                ),
                'is_enabled' => true,
                'permissions' => Employee::factory()->fakePermissions()
            ]
        );

        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->postJson('/employees', $data);
        $responseJson = $response->json();

        $response->assertStatus(201);
        $this->assertDatabaseHas('contacts', array_merge($contact->toArray(), ['contact_id' => $responseJson['contact_id']]));
        $this->assertDatabaseHas('employees', [
            'dob' => $employee->birth_date,
            'employee_number' => $employee->employee_number,
            'employee_id' => $responseJson['employee_id']
        ]);
        foreach($emailAddresses as $key => $emailAddress) {
            $emailData = $emailAddress->toArray();
            unset($emailData['type']);
            $emailData['type'] = $this->castAsJson($emailAddress->type);
            $emailData['contact_id'] = $responseJson['contact_id'];
            $this->assertDatabaseHas('email_addresses', $emailData);
        }
        foreach($phoneNumbers as $key => $phoneNumber)
            $this->assertDatabaseHas('phone_numbers', array_merge($phoneNumber->toArray(), ['contact_id' => $responseJson['contact_id']]));
    }

    public function testGetEmployeeForbidden() {
        // We do not have to populate email addresses, phone numbers, etc since we should be denied access right away
        $employee = Employee::factory()->create();

        $response = $this->get('/employees/' . $employee->employee_id);

        $response->assertStatus(403);
    }

    public function testGetEmployeeEditBasicBelongsToAuthenticatedUser() {
        $employee = Employee::factory()->create(['user_id' => $this->user]);
        $address = Address::factory()->create(['contact_id' => $employee->contact_id]);
        $emailAddresses = EmailAddress::factory()->count(4)->create(['contact_id' => $employee->contact_id]);
        $emailAddresses[0]->update(['is_primary' => true]);
        $phoneNumbers = PhoneNumber::factory()->count(3)->create(['contact_id' => $employee->contact_id]);
        $phoneNumbers[1]->update(['is_primary' => true]);

        $response = $this->get('/employees/' . $employee->employee_id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'contact',
                'employee_id',
                'is_driver',
                'is_enabled',
                'permissions' => [
                    'viewBasic',
                    'viewAdvanced',
                    'editBasic',
                    'editAdvanced',
                    'viewActivityLog'
                ],
                'updated_at',
            ])->assertJsonMissing([
                'employee_permissions',
                'insurance_expiration_date'
            ]);
    }


    public function testGetDriverEditBasicBelongsToAuthenticatedUser() {
        $employee = Employee::factory()->driver()->create(['user_id' => $this->user]);
        $address = Address::factory()->create(['contact_id' => $employee->contact_id]);
        $emailAddresses = EmailAddress::factory()->count(4)->create(['contact_id' => $employee->contact_id]);
        $emailAddresses[0]->update(['is_primary' => true]);
        $phoneNumbers = PhoneNumber::factory()->count(3)->create(['contact_id' => $employee->contact_id]);
        $phoneNumbers[1]->update(['is_primary' => true]);

        $response = $this->get('/employees/' . $employee->employee_id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'contact',
                'employee_id',
                'is_driver',
                'is_enabled',
                'permissions' => [
                    'viewBasic',
                    'viewAdvanced',
                    'editBasic',
                    'editAdvanced',
                    'viewActivityLog'
                ],
                'updated_at',
                'insurance_expiration_date',
                'license_plate_expiration_date',
                'drivers_license_expiration_date',
            ]);

        $data = $response->json();
        $this->assertArrayNotHasKey('employee_permissions', $data);
    }

    public function testGetEmployeeViewModelBasic() {
        $this->user->givePermissionTo('employees.view.basic.*');

        $employee = Employee::factory()->create();
        $emailAddresses = EmailAddress::factory()->count(4)->create(['contact_id' => $employee->contact_id]);
        $emailAddresses[0]->update(['is_primary' => true]);
        $phoneNumbers = PhoneNumber::factory()->count(3)->create(['contact_id' => $employee->contact_id]);
        $phoneNumbers[1]->update(['is_primary' => true]);

        $response = $this->get('/employees/' . $employee->employee_id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'contact',
                'employee_id',
                'is_driver',
                'is_enabled',
                'permissions' => [
                    'viewBasic',
                    'viewAdvanced',
                    'editBasic',
                    'editAdvanced',
                    'viewActivityLog'
                ],
                'updated_at',
            ])->assertJsonMissingPath('employee_permissions')
            ->assertJsonMissingPath('insurance_expiration_date');
    }

    public function testGetDriverViewModelBasic() {
        $this->user->givePermissionTo('employees.view.basic.*');

        $employee = Employee::factory()->driver()->create();
        $emailAddresses = EmailAddress::factory()->count(4)->create(['contact_id' => $employee->contact_id]);
        $emailAddresses[0]->update(['is_primary' => true]);
        $phoneNumbers = PhoneNumber::factory()->count(3)->create(['contact_id' => $employee->contact_id]);
        $phoneNumbers[1]->update(['is_primary' => true]);

        $response = $this->get('/employees/' . $employee->employee_id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'contact',
                'drivers_license_expiration_date',
                'employee_id',
                'insurance_expiration_date',
                'is_driver',
                'is_enabled',
                'license_plate_expiration_date',
                'permissions' => [
                    'editAdvanced',
                    'editBasic',
                    'viewActivityLog',
                    'viewAdvanced',
                    'viewBasic',
                ],
                'updated_at',
            ])->assertJsonMissingPath('employee_permissions')
            ->assertJsonMissingPath('drivers_license_number')
            ->assertJsonMissingPath('insurance_number')
            ->assertJsonMissingPath('license_number');
    }

    public function testGetEmployeeEditModelAdvanced() {
        $this->user->givePermissionTo('employees.edit.*.*');

        $employee = Employee::factory()->create();
        $address = Address::factory()->create(['contact_id' => $employee->contact_id]);
        $phones = PhoneNumber::factory()->count(2)->create(['contact_id' => $employee->contact_id]);
        $phones[1]->update(['is_primary' => true]);
        $emails = EmailAddress::factory()->count(3)->create(['contact_id' => $employee->contact_id, 'is_primary' => true]);
        $emails[0]->update(['is_primary' => true]);

        $response = $this->get('/employees/' . $employee->employee_id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'activity_log',
                'delivery_commission',
                'dob',
                'drivers_license_number',
                'employee_number',
                'employee_permissions',
                'insurance_number',
                'is_enabled',
                'license_plate_number',
                'pickup_commission',
                'sin',
                'start_date',
                'vehicle_type_id',
            ]);
    }

    public function testGetEmployeesList() {
        $this->user->givePermissionTo('employees.view.*.*');

        $employees = Employee::factory()->count(4)->create();
        $drivers = Employee::factory()->count(5)->create();
        $employees = $employees->merge($drivers);
        $users = [];

        foreach($employees as $employee) {
            $address = Address::factory()->create(['contact_id' => $employee->contact_id]);
            $phone = PhoneNumber::factory()->create(['contact_id' => $employee->contact_id]);
            $primaryPhone = PhoneNumber::factory()->create(['contact_id' => $employee->contact_id, 'is_primary' => true]);
            $emails = EmailAddress::factory()->count(2)->create(['contact_id' => $employee->contact_id]);
            $primaryEmail = EmailAddress::factory()->create(['contact_id' => $employee->contact_id, 'email' => $employee->user->email, 'is_primary' => true]);
        }

        $response = $this->get('/employees');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'company_name',
                        'employee_id',
                        'employee_name',
                        'employee_number',
                        'is_enabled',
                        'primary_email',
                        'primary_phone',
                        'user_id',
                    ]
                ],
                'queries'
            ]);
    }
}
