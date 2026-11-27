<?php

namespace App\Permissions;

use Spatie\Permission\WildcardPermission as SpatieWildcardPermission;

/**
 * Restores how spatie/laravel-permission 5.x matched wildcard permissions, which every permission and check in this
 * app was written against.
 *
 * 5.x let a held permission's trailing wildcard parts stand for "anything, or nothing": holding invoices.view.*.*
 * satisfied checks for invoices.view.*, invoices.view.my and invoices.view. 6.x only matches when the check reaches
 * the end of a held permission, so all of those fail - the Laravel 11 upgrade (6.25) locked admins out of invoices,
 * manifests and parts of account users, because the roles hold four-part *.* permissions while many checks name
 * three parts.
 *
 * Everything else is left to the package: only the case where the check runs out of parts first is changed.
 */
class WildcardPermission extends SpatieWildcardPermission
{
    protected function checkIndex(array $permission, array $index): bool
    {
        if (empty($permission))
            return $this->onlyWildcardsRemain($index);

        return parent::checkIndex($permission, $index);
    }

    // True if a held permission ends here, or continues only with wildcard parts (5.x: "every remaining part contains *")
    private function onlyWildcardsRemain(array $index): bool
    {
        if (array_key_exists('', $index))
            return true;

        return isset($index[static::WILDCARD_TOKEN])
            && is_array($index[static::WILDCARD_TOKEN])
            && $this->onlyWildcardsRemain($index[static::WILDCARD_TOKEN]);
    }
}
