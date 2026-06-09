<?php

namespace App\Services;

use App\Models\Note;
use App\Models\User;
use App\Repositories\CategoryRepository;
use App\Repositories\NoteRepository;
use Illuminate\Database\Eloquent\Collection;

class NoteService
{
    public function __construct(
        private readonly NoteRepository $notes,
        private readonly CategoryRepository $categories,
    ) {}

    /**
     * @param  array{category_id?: int}  $filters
     * @return Collection<int, Note>
     */
    public function list(User $user, array $filters = []): Collection
    {
        if (array_key_exists('category_id', $filters)) {
            $this->categories->findForUser($user, $filters['category_id']);
        }

        return $this->notes->forUser($user, $filters);
    }

    public function get(User $user, int $id): Note
    {
        return $this->notes->findForUser($user, $id);
    }

    /**
     * @param  array{category_id: int, title: string, content?: ?string}  $data
     */
    public function create(User $user, array $data): Note
    {
        $this->categories->findForUser($user, $data['category_id']);

        return $this->notes->createForUser($user, $data);
    }

    /**
     * @param  array{category_id?: int, title?: string, content?: ?string}  $data
     */
    public function update(User $user, int $id, array $data): Note
    {
        $note = $this->notes->findForUser($user, $id);

        if (array_key_exists('category_id', $data)) {
            $this->categories->findForUser($user, $data['category_id']);
        }

        return $this->notes->update($note, $data);
    }

    public function delete(User $user, int $id): void
    {
        $note = $this->notes->findForUser($user, $id);

        $this->notes->delete($note);
    }
}
