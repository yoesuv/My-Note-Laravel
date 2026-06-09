<?php

namespace Tests\Feature\Api\Note;

use App\Models\Category;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_notes(): void
    {
        $note = Note::factory()->create();

        $this->getJson('/api/notes')->assertUnauthorized();
        $this->postJson('/api/notes', [])->assertUnauthorized();
        $this->patchJson("/api/notes/{$note->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/notes/{$note->id}")->assertUnauthorized();
    }

    public function test_user_can_create_note_with_their_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Work',
            'name_normalized' => 'work',
            'color' => '#f39c12',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/notes', [
            'category_id' => $category->id,
            'title' => '  Sprint planning  ',
            'content' => '  Prepare backlog  ',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Note created successfully.')
            ->assertJsonPath('data.note.category_id', $category->id)
            ->assertJsonPath('data.note.title', 'Sprint planning')
            ->assertJsonPath('data.note.content', 'Prepare backlog')
            ->assertJsonPath('data.note.category.name', 'Work')
            ->assertJsonStructure([
                'data' => [
                    'note' => [
                        'id',
                        'category_id',
                        'title',
                        'content',
                        'category' => ['id', 'name', 'color', 'created_at', 'updated_at'],
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('notes', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Sprint planning',
            'content' => 'Prepare backlog',
        ]);
    }

    public function test_note_content_can_be_null(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/notes', [
            'category_id' => $category->id,
            'title' => 'Title only',
        ])
            ->assertCreated()
            ->assertJsonPath('data.note.content', null);

        $this->assertDatabaseHas('notes', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Title only',
            'content' => null,
        ]);
    }

    public function test_user_cannot_create_note_with_another_users_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/notes', [
            'category_id' => $otherCategory->id,
            'title' => 'Private note',
            'content' => 'Should not be created',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);

        $this->assertDatabaseMissing('notes', [
            'user_id' => $user->id,
            'category_id' => $otherCategory->id,
            'title' => 'Private note',
        ]);
    }

    public function test_title_is_required_and_trimmed_to_non_empty(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/notes', [
            'category_id' => $category->id,
            'title' => '   ',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_title_and_content_must_be_strings(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/notes', [
            'category_id' => $category->id,
            'title' => ['bad'],
            'content' => ['bad'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'content']);

        $this->postJson('/api/notes', [
            'category_id' => $category->id,
            'title' => 123,
            'content' => 456,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'content']);
    }

    public function test_content_has_maximum_length(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/notes', [
            'category_id' => $category->id,
            'title' => 'Long note',
            'content' => str_repeat('a', 65536),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }

    public function test_user_can_list_only_their_notes(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);

        Note::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'My note',
            'content' => 'Visible',
        ]);
        Note::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id,
            'title' => 'Other note',
            'content' => 'Hidden',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/notes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'My note')
            ->assertJsonPath('data.0.content', 'Visible');
    }

    public function test_user_can_filter_notes_by_their_category(): void
    {
        $user = User::factory()->create();
        $work = Category::factory()->create(['user_id' => $user->id, 'name' => 'Work', 'name_normalized' => 'work']);
        $personal = Category::factory()->create(['user_id' => $user->id, 'name' => 'Personal', 'name_normalized' => 'personal']);

        Note::factory()->create(['user_id' => $user->id, 'category_id' => $work->id, 'title' => 'Work note']);
        Note::factory()->create(['user_id' => $user->id, 'category_id' => $personal->id, 'title' => 'Personal note']);

        Sanctum::actingAs($user);

        $this->getJson("/api/notes?category_id={$work->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Work note')
            ->assertJsonPath('data.0.category_id', $work->id);
    }

    public function test_user_cannot_filter_notes_by_another_users_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);

        Sanctum::actingAs($user);

        $this->getJson("/api/notes?category_id={$otherCategory->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_user_can_show_their_note(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $note = Note::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Ideas',
            'content' => 'Build notes API',
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/notes/{$note->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Ideas')
            ->assertJsonPath('data.content', 'Build notes API')
            ->assertJsonPath('data.category_id', $category->id);
    }

    public function test_user_can_update_note_and_move_it_to_their_category(): void
    {
        $user = User::factory()->create();
        $work = Category::factory()->create(['user_id' => $user->id, 'name' => 'Work', 'name_normalized' => 'work']);
        $ideas = Category::factory()->create(['user_id' => $user->id, 'name' => 'Ideas', 'name_normalized' => 'ideas']);
        $note = Note::factory()->create([
            'user_id' => $user->id,
            'category_id' => $work->id,
            'title' => 'Old title',
            'content' => 'Old content',
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/notes/{$note->id}", [
            'category_id' => $ideas->id,
            'title' => '  New title  ',
            'content' => '  New content  ',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Note updated successfully.')
            ->assertJsonPath('data.note.category_id', $ideas->id)
            ->assertJsonPath('data.note.title', 'New title')
            ->assertJsonPath('data.note.content', 'New content')
            ->assertJsonPath('data.note.category.name', 'Ideas');

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'user_id' => $user->id,
            'category_id' => $ideas->id,
            'title' => 'New title',
            'content' => 'New content',
        ]);
    }

    public function test_user_cannot_move_note_to_another_users_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);
        $note = Note::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/notes/{$note->id}", [
            'category_id' => $otherCategory->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_user_cannot_access_another_users_note(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);
        $note = Note::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id,
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/notes/{$note->id}")->assertNotFound();
        $this->patchJson("/api/notes/{$note->id}", ['title' => 'Hacked'])->assertNotFound();
        $this->deleteJson("/api/notes/{$note->id}")->assertNotFound();
    }

    public function test_malformed_note_id_returns_not_found(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/notes/1abc')->assertNotFound();
        $this->patchJson('/api/notes/1abc', ['title' => 'Updated'])->assertNotFound();
        $this->deleteJson('/api/notes/1abc')->assertNotFound();
    }

    public function test_user_can_delete_note(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $note = Note::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/notes/{$note->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Note deleted successfully.');

        $this->assertDatabaseMissing('notes', [
            'id' => $note->id,
        ]);
    }

    public function test_deleting_category_deletes_its_notes(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $note = Note::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/categories/{$category->id}")->assertOk();

        $this->assertDatabaseMissing('notes', [
            'id' => $note->id,
        ]);
    }
}
