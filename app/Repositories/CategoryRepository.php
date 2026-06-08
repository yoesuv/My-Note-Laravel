<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    /**
     * @return Collection<int, Category>
     */
    public function forUser(User $user): Collection
    {
        return $user->categories()
            ->orderBy('name')
            ->get();
    }

    public function findForUser(User $user, int $id): Category
    {
        return $user->categories()->findOrFail($id);
    }

    /**
     * @param  array{name: string, name_normalized: string, color: string}  $data
     */
    public function createForUser(User $user, array $data): Category
    {
        return $user->categories()->create($data);
    }

    /**
     * @param  array{name?: string, name_normalized?: string, color?: string}  $data
     */
    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->refresh();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    public function existsByNormalizedName(User $user, string $normalizedName, ?int $ignoreId = null): bool
    {
        return $user->categories()
            ->where('name_normalized', $normalizedName)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
