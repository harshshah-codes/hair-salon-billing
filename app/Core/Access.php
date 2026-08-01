<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Role/permission evaluation against a user's role permissions.
 */
class Access
{
    public static function can(string $roleSlug, $permissions, string $permission): bool
    {
        if ($roleSlug === 'admin') {
            return true;
        }
        if (!is_array($permissions)) {
            return false;
        }
        if (isset($permissions['*']) && $permissions['*'] === true) {
            return true;
        }

        $parts = explode('.', $permission, 2);
        $section = $parts[0];
        $action = $parts[1] ?? null;

        if (!array_key_exists($section, $permissions)) {
            return false;
        }
        $value = $permissions[$section];
        if ($value === true) {
            return true;
        }
        if (is_array($value)) {
            return in_array('*', $value, true) || ($action !== null && in_array($action, $value, true));
        }
        return false;
    }
}
