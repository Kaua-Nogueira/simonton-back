<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('categories.index'); // Route is apiResource('categories') -> categories.index
    }

    public function view(User $user, Category $category): bool
    {
        return $user->hasPermission('categories.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('categories.store');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasPermission('categories.update');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasPermission('categories.destroy');
    }
}
