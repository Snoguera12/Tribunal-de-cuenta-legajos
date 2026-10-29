<?php

namespace App\Policies;

use App\Models\ApiClient;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ApiClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ApiClient $apiClient): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ApiClient $apiClient): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ApiClient $apiClient): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, ApiClient $apiClient): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, ApiClient $apiClient): bool
    {
        return $user->isAdmin();
    }
}
