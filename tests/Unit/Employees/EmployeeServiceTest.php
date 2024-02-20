<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Employee;
use App\Models\EmployeeEmergencyContact;
use App\Models\User;
use App\Models\Address;
use App\Models\Contact;
use App\Models\PhoneNumber;
use App\Models\EmailAddress;
use App\Services\ContactService;
use App\Services\EmployeeService;
use App\Services\UserService;
use Spatie\Permission\Models\Permission;

class EmployeeServiceTest extends TestCase {
    use RefreshDatabase;

    protected function setUp() : void {
        parent::setUp();

        $contactService = new ContactService();
        $userService = new UserService();
        $this->employeeService = new EmployeeService($contactService, $userService);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function assertPermissionsEqual($userId, $permissions) {
        $permissionsMap = array_flip(Employee::$permissionsMap);
        foreach($permissions as $permission => $value) {
            $mappedPermission = $permissionsMap[$permission];
            $permissionId = Permission::where('name', $mappedPermission)->first()->id;
            if($value)
                $this->assertDatabaseHas('model_has_permissions', [
                    'model_id' => $userId,
                    'permission_id' => $permissionId
                ]);
            else
                $this->assertDatabaseMissing('model_has_permissions', [
                    'model_id' => $userId,
                    'permission_id' => $permissionId
                ]);
        }
    }

    public function testEmployeeCreate() {
        $this->user->givePermissionTo('employees.create');

        $employee = Employee::factory()->make(['contact_id' => null, 'user_id' => null]);
        $employeeData = array_merge($employee->toArray(), ['is_enabled' => true, 'permissions' => Employee::factory()->fakePermissions()]);
        $contactData = Contact::factory()->make(['contact_id' => null]);
        $address = Address::factory()->make(['contact_id' => null]);
        $phone1 = PhoneNumber::factory()->make(['contact_id' => null, 'is_primary' => true]);
        $phone2 = PhoneNumber::factory()->make(['contact_id' => null]);
        $email1 = EmailAddress::factory()->make(['contact_id' => null, 'is_primary' => true]);
        $email2 = EmailAddress::factory()->make(['contact_id' => null]);

        $formData = array_merge(
            $employeeData,
            ['contact' => array_merge(
                $contactData->toArray(),
                ['address' => $address->toArray()],
                ['email_addresses' => [
                    $email1->toArray(),
                    $email2->toArray()
                ]],
                ['phone_numbers' => [
                    $phone1->toArray(),
                    $phone2->toArray()
                ]]
            )],
        );

        $this->employeeService->create($formData);

        $contactValidation = $contactData->toArray();
        unset($contactValidation['contact_id']);
        $employeeValidation = $employee->toArray();
        unset(
            $employeeValidation['employee_id'],
            $employeeValidation['contact_id'],
            $employeeValidation['user_id'],
        );

        $this->assertDatabaseHas('contacts', $contactValidation);
        $this->assertDatabaseHas('employees', $employeeValidation);
        $this->assertDatabaseHas('users', ['is_enabled' => true, 'email' => $email1->email]);
    }

    public function testDriverCreate() {
        $this->user->givePermissionTo('employees.create');

        $employee = Employee::factory()->make(['contact_id' => null, 'user_id' => null]);
        $contactData = Contact::factory()->make(['contact_id' => null]);
        $address = Address::factory()->make(['contact_id' => null]);
        $phone1 = PhoneNumber::factory()->make(['contact_id' => null, 'is_primary' => true]);
        $phone2 = PhoneNumber::factory()->make(['contact_id' => null]);
        $email1 = EmailAddress::factory()->make(['contact_id' => null, 'is_primary' => true]);
        $email2 = EmailAddress::factory()->make(['contact_id' => null]);
        $email3 = EmailAddress::factory()->make(['contact_id' => null]);

        $formData = array_merge(
            $employee->toArray(),
            ['contact' => array_merge(
                $contactData->toArray(),
                ['address' => $address->toArray()],
                ['email_addresses' => [
                    $email1->toArray(),
                    $email2->toArray(),
                    $email3->toArray()
                ]],
                ['phone_numbers' => [
                    $phone1->toArray(),
                    $phone2->toArray()
                ]]
            )],
            ['is_enabled' => true, 'permissions' => Employee::factory()->fakePermissions()]
        );

        $this->employeeService->create($formData);

        $contactValidation = $contactData->toArray();
        unset($contactValidation['contact_id']);
        $employeeValidation = $employee->toArray();
        unset(
            $employeeValidation['employee_id'],
            $employeeValidation['contact_id'],
            $employeeValidation['user_id'],
        );

        $this->assertDatabaseHas('contacts', $contactValidation);
        $this->assertDatabaseHas('employees', $employeeValidation);
        $this->assertDatabaseHas('users', ['is_enabled' => true, 'email' => $email1->email]);
    }

    public function testUpdateEmployeeFull() {
        $this->user->givePermissionTo('employees.edit.*.*');

        $employee = Employee::factory()->driver()->create();
        $address = Address::factory()->create(['contact_id' => $employee->contact_id]);
        $phone1 = PhoneNumber::factory()->create(['contact_id' => $employee->contact_id, 'is_primary' => true]);
        $phone2 = PhoneNumber::factory()->create(['contact_id' => $employee->contact_id]);
        $email1 = EmailAddress::factory()->create(['contact_id' => $employee->contact_id, 'is_primary' => true]);
        $email2 = EmailAddress::factory()->create(['contact_id' => $employee->contact_id]);
        $email3 = EmailAddress::factory()->create(['contact_id' => $employee->contact_id]);

        $updatedEmployee = Employee::factory()->driver()->make([
            'contact_id' => $employee->contact_id,
            'employee_id' => $employee->employee_id,
            'user_id' => $employee->user_id
        ]);
        $updatedContact = Contact::factory()->make(['contact_id' => $employee->contact_id]);
        $updatedAddress = Address::factory()->make(['contact_id' => $employee->contact_id]);
        $updatedEmployeeData = array_merge($updatedEmployee->toArray());
        $updatedPhone1 = PhoneNumber::factory()->make(['contact_id' => $employee->contact_id, 'is_primary' => true, 'phone_number_id' => $phone1->phone_number_id]);
        $updatedPhone2 = PhoneNumber::factory()->make(['contact_id' => $employee->contact_id, 'phone_number_id' => $phone2->phone_number_id]);
        $updatedEmail1 = EmailAddress::factory()->make(['contact_id' => $employee->contact_id, 'is_primary' => false, 'email_address_id' => $email1->email_address_id]);
        $updatedEmail2 = EmailAddress::factory()->make(['contact_id' => $employee->contact_id, 'is_primary' => true, 'email_address_id' => $email2->email_address_id]);
        $updatedEmail3 = EmailAddress::factory()->make(['contact_id' => $employee->contact_id, 'email_address_id' => $email3->email_address_id, 'delete' => true]);

        $formData = array_merge(
            $updatedEmployeeData,
            ['contact' => array_merge(
                $updatedContact->toArray(),
                ['email_addresses' => [
                    $updatedEmail1->toArray(),
                    $updatedEmail2->toArray(),
                    $updatedEmail3->toArray()
                ]],
                ['phone_numbers' => [
                    $updatedPhone1->toArray(),
                    $updatedPhone2->toArray()
                ]]
            )],
            [
                'is_enabled' => false,
                'permissions' => Employee::factory()->fakePermissions()
            ]
        );

        $result = $this->employeeService->update($formData);

        $this->assertDatabaseHas('contacts', $updatedContact->toArray());
        $this->assertDatabaseHas('employees', $updatedEmployeeData);
        $this->assertDatabaseHas('users', ['is_enabled' => false, 'email' => $updatedEmail2->email]);
        $this->assertDatabaseCount('contacts', 1);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseHas('email_addresses', array_merge($updatedEmail1->toArray(), ['type' => $this->castAsJson($updatedEmail1->type)]));
        $this->assertDatabaseHas('email_addresses', array_merge($updatedEmail2->toArray(), ['type' => $this->castAsJson($updatedEmail2->type)]));
        $this->assertDatabaseMissing('email_addresses', ['email' => $email3->email]);
        $this->assertDatabaseMissing('email_addresses', ['email' => $updatedEmail3->email]);
        $this->assertDatabaseHas('phone_numbers', $updatedPhone1->toArray());
        $this->assertDatabaseHas('phone_numbers', $updatedPhone2->toArray());
        $this->assertPermissionsEqual($employee->user->user_id, $formData['permissions']);
    }

}
