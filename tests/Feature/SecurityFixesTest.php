<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Address;
use App\Models\Contact;
use App\Models\EmailAddress;
use App\Models\User;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Pins the fixes from the November 2026 security audit, so a later change can't quietly reopen them.
 */
class SecurityFixesTest extends TestCase {
    use RefreshDatabase;

    // See ZoneSaveTest: APP_URL carries a /app path, so absolute URLs are used to hit the real routes
    private const HOST = 'http://localhost';

    protected function setUp(): void {
        parent::setUp();
        // phpunit.xml does not set APP_ENV=testing, so CSRF stays on unless disabled (see ZoneSaveTest)
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    protected function tearDown(): void {
        // Trusted hosts are static on Symfony's Request and would leak into later tests
        Request::setTrustedHosts([]);
        parent::tearDown();
    }

    /** SQL injection: manifest ids are concatenated into raw SQL, so only digits may reach the controller */
    public function test_manifest_routes_reject_non_numeric_ids() {
        $this->actingAs(User::factory()->create());

        $this->get(self::HOST . '/manifests/print/1%20and%20sleep(1)')->assertNotFound();
        $this->get(self::HOST . '/manifests/download/1,2%20or%201=1')->assertNotFound();
        $this->get(self::HOST . '/manifests/1abc')->assertNotFound();
    }

    /** The token login issued tokens without checking the password; it is removed until mobile resumes */
    public function test_token_login_route_is_gone() {
        $this->postJson(self::HOST . '/api/login', [
            'email' => 'anyone@example.com', 'password' => 'x', 'device_name' => 'phone'
        ])->assertNotFound();
    }

    public function test_web_login_locks_out_after_repeated_failures() {
        // Unique per run: the lockout lives in the cache, which may persist between runs
        $email = 'lockout-' . uniqid() . '@example.com';

        for($i = 0; $i < 10; $i++)
            $this->post(self::HOST . '/login', ['email' => $email, 'password' => 'wrong']);

        $response = $this->post(self::HOST . '/login', ['email' => $email, 'password' => 'wrong']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
    }

    /** Host-header poisoning: a forged Host would otherwise end up in password reset links */
    public function test_untrusted_host_is_rejected_in_production() {
        $this->app['env'] = 'prod';
        config(['app.url' => 'https://fastforwardexpress.ca', 'branding.marketing_url' => '/']);

        $this->get('https://evil.example/password/reset')->assertNotFound();

        // Our own domain and its subdomains must still get through
        $this->assertNotEquals(404, $this->get('https://fastforwardexpress.ca/password/reset')->getStatusCode());
        $this->assertNotEquals(404, $this->get('https://app.fastforwardexpress.ca/password/reset')->getStatusCode());
    }

    public function test_stripe_receipt_tool_requires_an_admin() {
        // Guests and non-admins are stopped before any Stripe call is made
        $this->getJson(self::HOST . '/api/tools/getStripeReceipts')->assertUnauthorized();

        $this->actingAs(User::factory()->create());
        $this->getJson(self::HOST . '/api/tools/getStripeReceipts')->assertForbidden();
    }

    /**
     * Cross-account takeover: a customer admin could link themselves into any other customer's account,
     * because only the user being linked was checked - never the account they were being linked into.
     */
    public function test_customer_admin_cannot_link_a_user_into_someone_elses_account() {
        [$attacker, $attackerContactId] = $this->makeAccountUser($this->makeAccount('Mine'), 'accountUsers.create.my');
        $victimAccountId = $this->makeAccount('Victim');

        $this->actingAs($attacker)
            ->postJson(self::HOST . "/accountUsers/link/{$attackerContactId}/{$victimAccountId}")
            ->assertForbidden();

        $this->assertDatabaseMissing('account_users', ['account_id' => $victimAccountId, 'contact_id' => $attackerContactId]);
    }

    public function test_staff_can_still_link_a_user_into_another_account() {
        [, $contactId] = $this->makeAccountUser($this->makeAccount('Customer One'));
        $otherAccountId = $this->makeAccount('Customer Two');

        Permission::findOrCreate('accountUsers.create.*.*', 'web');
        $staff = User::factory()->create();
        $staff->givePermissionTo('accountUsers.create.*.*');

        $this->actingAs($staff)
            ->postJson(self::HOST . "/accountUsers/link/{$contactId}/{$otherAccountId}")
            ->assertOk();

        $this->assertDatabaseHas('account_users', ['account_id' => $otherAccountId, 'contact_id' => $contactId]);
    }

    /** CSRF: as a GET, a staff admin clicking a crafted link would perform the link */
    public function test_link_cannot_be_triggered_by_a_get_request() {
        [, $contactId] = $this->makeAccountUser($this->makeAccount('Customer One'));
        $otherAccountId = $this->makeAccount('Customer Two');

        Permission::findOrCreate('accountUsers.create.*.*', 'web');
        $staff = User::factory()->create();
        $staff->givePermissionTo('accountUsers.create.*.*');

        $this->actingAs($staff)
            ->get(self::HOST . "/accountUsers/link/{$contactId}/{$otherAccountId}")
            ->assertStatus(405);

        $this->assertDatabaseMissing('account_users', ['account_id' => $otherAccountId, 'contact_id' => $contactId]);
    }

    /** Staff who can only edit account users may not add memberships (kept strict on purpose) */
    public function test_staff_with_only_edit_rights_cannot_link() {
        [, $contactId] = $this->makeAccountUser($this->makeAccount('Customer One'));
        $otherAccountId = $this->makeAccount('Customer Two');

        Permission::findOrCreate('accountUsers.edit.*.*', 'web');
        $staff = User::factory()->create();
        $staff->givePermissionTo('accountUsers.edit.*.*');

        $this->actingAs($staff)
            ->postJson(self::HOST . "/accountUsers/link/{$contactId}/{$otherAccountId}")
            ->assertForbidden();
    }

    /** Contact overwrite: a contact_id from outside the account must not be editable through it */
    public function test_store_account_user_rejects_a_contact_from_another_account() {
        $myAccountId = $this->makeAccount('Mine');
        [$admin] = $this->makeAccountUser($myAccountId, 'accountUsers.create.my');
        $staffContact = Contact::factory()->create(['first_name' => 'Original']);

        $this->actingAs($admin)
            ->postJson(self::HOST . '/accountUsers', [
                'account_id' => $myAccountId,
                'contact_id' => $staffContact->contact_id,
                'first_name' => 'Overwritten',
            ])->assertForbidden();

        $this->assertDatabaseHas('contacts', ['contact_id' => $staffContact->contact_id, 'first_name' => 'Original']);
    }

    /** Email lookup: a customer admin learns an address is taken, but not who holds it or their accounts */
    public function test_email_lookup_hides_other_customers_details() {
        $myAccountId = $this->makeAccount('Mine');
        [$admin] = $this->makeAccountUser($myAccountId, 'accountUsers.create.my');
        [$stranger] = $this->makeAccountUser($this->makeAccount('Someone Else'));

        $this->actingAs($admin)
            ->postJson(self::HOST . '/accountUsers/checkIfExists', [
                'account_id' => $myAccountId,
                'email_addresses' => [['email' => $stranger->email]],
            ])
            ->assertOk()
            ->assertJson(['email_in_use' => true, 'contact_id' => null, 'name' => null, 'accounts' => []]);
    }

    /** A customer admin may see a shared user's details, but only the accounts within their own scope */
    public function test_email_lookup_lists_only_accounts_the_asker_can_see() {
        $myAccountId = $this->makeAccount('Mine');
        $otherCompanyId = $this->makeAccount('Other Company');
        [$admin] = $this->makeAccountUser($myAccountId, 'accountUsers.create.my');
        [$colleague, $colleagueContactId] = $this->makeAccountUser($myAccountId);
        // The same person also works for another company
        DB::table('account_users')->insert([
            'account_id' => $otherCompanyId, 'contact_id' => $colleagueContactId, 'user_id' => $colleague->id, 'is_primary' => false
        ]);

        $response = $this->actingAs($admin)
            ->postJson(self::HOST . '/accountUsers/checkIfExists', [
                'account_id' => $myAccountId,
                'email_addresses' => [['email' => $colleague->email]],
            ])
            ->assertOk()
            ->assertJson(['email_in_use' => true, 'contact_id' => $colleagueContactId]);

        $this->assertEquals([$myAccountId], array_column($response->json('accounts'), 'value'));
    }

    public function test_email_lookup_requires_rights_on_the_account() {
        $this->actingAs(User::factory()->create())
            ->postJson(self::HOST . '/accountUsers/checkIfExists', [
                'account_id' => $this->makeAccount('Not Yours'),
                'email_addresses' => [['email' => 'someone@example.com']],
            ])->assertForbidden();
    }

    /**
     * Helpers
     */
    private function makeAccount($name) {
        $address = Address::factory()->create();

        return DB::table('accounts')->insertGetId([
            'account_number' => uniqid('T'),
            'invoice_interval' => 'monthly',
            'invoice_sort_order' => '[]',
            'name' => $name . ' ' . uniqid(),
            'shipping_address_id' => $address->address_id,
            'start_date' => now()->toDateString(),
        ], 'account_id');
    }

    /** A user who is an account user of $accountId, with a contact whose primary email is the user's email */
    private function makeAccountUser($accountId, $permission = null) {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        EmailAddress::factory()->create(['contact_id' => $contact->contact_id, 'email' => $user->email, 'is_primary' => true]);
        DB::table('account_users')->insert([
            'account_id' => $accountId, 'contact_id' => $contact->contact_id, 'user_id' => $user->id, 'is_primary' => true
        ]);

        if($permission) {
            Permission::findOrCreate($permission, 'web');
            $user->givePermissionTo($permission);
        }

        return [$user->fresh(), $contact->contact_id];
    }
}
