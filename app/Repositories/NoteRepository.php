<?php

namespace App\Repositories;

use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class NoteRepository
{
    /**
     * @param  array{category_id?: int}  $filters
     * @return Collection<int, Note>
     */
    public function forUser(User $user, array $filters = []): Collection
    {
        return $user->notes()
            ->with('category')
            ->when(array_key_exists('category_id', $filters), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->latest('updated_at')
            ->latest('id')
            ->get();
    }

    public function findForUser(User $user, int $id): Note
    {
        return $user->notes()
            ->with('category')
            ->findOrFail($id);
    }

    /**
     * @param  array{category_id: int, title: string, content?: ?string}  $data
     */
    public function createForUser(User $user, array $data): Note
    {
        return $user->notes()
            ->create($data)
            ->load('category');
    }

    /**
     * @param  array{category_id?: int, title?: string, content?: ?string}  $data
     */
    public function update(Note $note, array $data): Note
    {
        $note->update($data);

        return $note->load('category');
    }

    public function delete(Note $note): void
    {
        $note->delete();
    }
}
