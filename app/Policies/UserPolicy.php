<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function view(User $user, User $model): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isSupervisor()) {
            return $model->isOperator() && $model->supervisor_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function update(User $user, User $model): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isSupervisor()) {
            return $model->isOperator() && $model->supervisor_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isSupervisor()) {
            return $model->isOperator() && $model->supervisor_id === $user->id;
        }

        return false;
    }
}
