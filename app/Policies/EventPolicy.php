<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;

class EventPolicy extends PermissionPolicy
{
    protected function resource(): string
    {
        return 'events';
    }

    /**
     * Whether the user may publish an event or take it back to a draft.
     */
    public function publish(User $user): bool
    {
        return $user->can(Permissions::EVENTS_PUBLISH);
    }
}
