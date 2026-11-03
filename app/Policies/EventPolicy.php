<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

class EventPolicy extends PermissionPolicy
{
    protected function resource(): string
    {
        return 'events';
    }

    public function view(User $user, Model $model): bool
    {
        return parent::view($user, $model) && $this->ownsOrModerates($user, $model);
    }

    public function update(User $user, Model $model): bool
    {
        return parent::update($user, $model) && $this->ownsOrModerates($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return parent::delete($user, $model) && $this->ownsOrModerates($user, $model);
    }

    /**
     * Whether the user may publish an event or take it back to a draft.
     */
    public function publish(User $user): bool
    {
        return $user->can(Permissions::EVENTS_PUBLISH);
    }

    /**
     * Those who can publish moderate every event; everyone else only reaches the
     * events of their own organizer.
     */
    private function ownsOrModerates(User $user, Model $event): bool
    {
        if (! $event instanceof Event || ! $event->exists || $user->can(Permissions::EVENTS_PUBLISH)) {
            return true;
        }

        return $user->organizer_id !== null && $event->organizer_id === $user->organizer_id;
    }
}
