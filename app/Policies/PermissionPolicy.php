<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/**
 * The base of the admin policies: an ability is allowed when the user holds the
 * permission of the same name for the resource, whatever role gave it to them.
 */
abstract class PermissionPolicy
{
    /**
     * The resource the permissions are named after, e.g. `events`.
     */
    abstract protected function resource(): string;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'viewAny');
    }

    public function view(User $user, Model $model): bool
    {
        return $this->allows($user, 'viewAny');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'create');
    }

    public function update(User $user, Model $model): bool
    {
        return $this->allows($user, 'update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->allows($user, 'delete');
    }

    protected function allows(User $user, string $ability): bool
    {
        return $user->can(Permissions::for($this->resource(), $ability));
    }
}
