<?php

namespace App\Support;

/**
 * The permissions the admin area is built on. Access is checked against these,
 * never against a role name, so a new role is a seeder or database change.
 */
class Permissions
{
    /**
     * Opens the admin area at all.
     */
    public const ADMIN_ACCESS = 'admin.access';

    /**
     * The managed resources and the abilities every one of them has.
     */
    private const RESOURCES = ['events', 'races', 'series', 'regions'];

    private const ABILITIES = ['viewAny', 'create', 'update', 'delete'];

    /**
     * The permission to publish an event, which is more than editing it.
     */
    public const EVENTS_PUBLISH = 'events.publish';

    /**
     * The permission name for an ability on a resource, e.g. `events.update`.
     */
    public static function for(string $resource, string $ability): string
    {
        return "{$resource}.{$ability}";
    }

    /**
     * Every permission the application knows.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        $permissions = [self::ADMIN_ACCESS];

        foreach (self::RESOURCES as $resource) {
            foreach (self::ABILITIES as $ability) {
                $permissions[] = self::for($resource, $ability);
            }
        }

        $permissions[] = self::EVENTS_PUBLISH;

        return $permissions;
    }
}
