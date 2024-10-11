<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('user:view');
    }

    public function create(User $user): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return $user->checkPermissionTo('user:create');
    }

    public function view(User $user, User $model): bool
    {
        if ($model->is($user)) {
            return true;
        }

        return $user->checkPermissionTo('user:view');
    }

    public function update(User $user, User $model): bool
    {
        if ($model->is($user)) {
            return true;
        }

        if (! $this->view($user, $model)) {
            return false;
        }

        return $user->checkPermissionTo('user:update');
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->is($user)) {
            return false;
        }

        if (! $this->update($user, $model)) {
            return false;
        }

        return $user->checkPermissionTo('user:delete');
    }

    public function restore(User $user, User $model): bool
    {
        if ($model->is($user)) {
            return false;
        }

        if (! $this->update($user, $model)) {
            return false;
        }

        return $user->checkPermissionTo('user:restore');
    }
}
