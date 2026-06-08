<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepository $categories,
    ) {}

    /**
     * @return Collection<int, Category>
     */
    public function list(User $user): Collection
    {
        return $this->categories->forUser($user);
    }

    public function get(User $user, int $id): Category
    {
        return $this->categories->findForUser($user, $id);
    }

    /**
     * @param  array{name: string, color: string}  $data
     *
     * @throws ValidationException
     */
    public function create(User $user, array $data): Category
    {
        $normalizedName = $this->normalizeName($data['name']);

        $this->ensureNameIsUnique($user, $normalizedName);

        try {
            return $this->categories->createForUser($user, [
                'name' => $data['name'],
                'name_normalized' => $normalizedName,
                'color' => $data['color'],
            ]);
        } catch (QueryException $exception) {
            $this->handleUniqueNameQueryException($exception);
        }
    }

    /**
     * @param  array{name?: string, color?: string}  $data
     *
     * @throws ValidationException
     */
    public function update(User $user, int $id, array $data): Category
    {
        $category = $this->categories->findForUser($user, $id);
        $attributes = [];

        if (array_key_exists('name', $data)) {
            $normalizedName = $this->normalizeName($data['name']);
            $this->ensureNameIsUnique($user, $normalizedName, $category->id);

            $attributes['name'] = $data['name'];
            $attributes['name_normalized'] = $normalizedName;
        }

        if (array_key_exists('color', $data)) {
            $attributes['color'] = $data['color'];
        }

        try {
            return $this->categories->update($category, $attributes);
        } catch (QueryException $exception) {
            $this->handleUniqueNameQueryException($exception);
        }
    }

    public function delete(User $user, int $id): void
    {
        $category = $this->categories->findForUser($user, $id);

        $this->categories->delete($category);
    }

    private function normalizeName(string $name): string
    {
        return Str::lower($name);
    }

    /**
     * @throws ValidationException
     */
    private function ensureNameIsUnique(User $user, string $normalizedName, ?int $ignoreId = null): void
    {
        if (! $this->categories->existsByNormalizedName($user, $normalizedName, $ignoreId)) {
            return;
        }

        $this->throwNameTakenValidationException();
    }

    /**
     * @throws QueryException
     * @throws ValidationException
     */
    private function handleUniqueNameQueryException(QueryException $exception): never
    {
        if (in_array($exception->getCode(), ['23000', '23505'], true)) {
            $this->throwNameTakenValidationException();
        }

        throw $exception;
    }

    /**
     * @throws ValidationException
     */
    private function throwNameTakenValidationException(): never
    {
        throw ValidationException::withMessages([
            'name' => ['The name has already been taken.'],
        ]);
    }
}
