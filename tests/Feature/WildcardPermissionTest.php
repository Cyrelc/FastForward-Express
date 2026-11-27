<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Manifest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Roles hold four-part permissions like invoices.view.*.*, while many checks name fewer or more specific parts
 * (invoices.view.*, invoices.view.my). spatie/laravel-permission 5.x matched those; 6.x doesn't, which locked admins
 * out of invoices and manifests after the Laravel 11 upgrade. App\Permissions\WildcardPermission keeps the 5.x rules.
 */
class WildcardPermissionTest extends TestCase {
    use RefreshDatabase;

    private function userWith(...$permissions) {
        $user = User::factory()->create();
        foreach($permissions as $permission)
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));

        return $user->fresh();
    }

    public function test_trailing_wildcards_satisfy_shorter_and_more_specific_checks() {
        $user = $this->userWith('invoices.view.*.*');

        foreach(['invoices.view.*.*', 'invoices.view.*', 'invoices.view', 'invoices.view.my', 'invoices.view.children', 'invoices.view.basic.my'] as $check)
            $this->assertTrue($user->checkPermissionTo($check), "invoices.view.*.* should satisfy $check");
    }

    public function test_wildcards_do_not_reach_past_what_is_held() {
        $user = $this->userWith('invoices.view.*.*', 'bills.view.basic.my');

        foreach(['invoices.edit.*', 'invoices.edit.my', 'invoices', 'invoices.*', 'manifests.view.*'] as $check)
            $this->assertFalse($user->checkPermissionTo($check), "invoices.view.*.* must not satisfy $check");

        // A specific permission never satisfies a wildcard check, nor a sibling
        $this->assertFalse($user->checkPermissionTo('bills.view.basic.*'));
        $this->assertFalse($user->checkPermissionTo('bills.view.basic.children'));
        $this->assertTrue($user->checkPermissionTo('bills.view.basic.my'));
    }

    public function test_a_shorter_held_permission_covers_everything_beneath_it() {
        $user = $this->userWith('bills.delete');

        $this->assertTrue($user->checkPermissionTo('bills.delete'));
        $this->assertTrue($user->checkPermissionTo('bills.delete.anything'));
        $this->assertFalse($user->checkPermissionTo('bills'));
    }

    public function test_admins_holding_star_star_permissions_can_open_invoices_and_manifests() {
        $admin = $this->userWith('invoices.view.*.*', 'manifests.view.*.*');

        $this->assertTrue($admin->can('view', new Invoice()));
        $this->assertTrue($admin->can('viewAny', Manifest::class));
        $this->assertTrue($admin->can('view', new Manifest()));

        // InvoicePolicy::view once passed both to can(), which only ever checked the first (and since 6.x reads a
        // string second argument as the guard, so checked nothing)
        $this->assertTrue($this->userWith('invoices.edit.*.*')->can('view', new Invoice()));

        $nobody = $this->userWith('bills.view.basic.my');
        $this->assertFalse($nobody->can('view', new Invoice()));
        $this->assertFalse($nobody->can('viewAny', Manifest::class));
    }
}
