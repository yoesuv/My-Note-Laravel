<?php

namespace Tests\Feature\Api\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_categories(): void
    {
        $category = Category::factory()->create();

        $this->getJson('/api/categories')->assertUnauthorized();
        $this->postJson('/api/categories', [])->assertUnauthorized();
        $this->patchJson("/api/categories/{$category->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/categories/{$category->id}")->assertUnauthorized();
    }

    public function test_user_can_list_only_their_categories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Work',
            'name_normalized' => 'work',
            'color' => '#f39c12',
        ]);
        Category::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Private',
            'name_normalized' => 'private',
            'color' => '#3498db',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/categories');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Work')
            ->assertJsonPath('data.0.color', '#f39c12');
    }

    public function test_user_can_create_category_with_trimmed_name_and_normalized_color(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/categories', [
            'name' => '  Work  ',
            'color' => '#F39C12',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Category created successfully.')
            ->assertJsonPath('data.category.name', 'Work')
            ->assertJsonPath('data.category.color', '#f39c12')
            ->assertJsonStructure([
                'data' => ['category' => ['id', 'name', 'color', 'created_at', 'updated_at']],
            ]);

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Work',
            'name_normalized' => 'work',
            'color' => '#f39c12',
        ]);
    }

    public function test_category_name_is_unique_per_user_case_insensitively(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/categories', [
            'name' => 'Work',
            'color' => '#f39c12',
        ])->assertCreated();

        $response = $this->postJson('/api/categories', [
            'name' => ' work ',
            'color' => '#3498db',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_different_users_can_use_same_category_name(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        Category::factory()->create([
            'user_id' => $firstUser->id,
            'name' => 'Work',
            'name_normalized' => 'work',
            'color' => '#f39c12',
        ]);

        Sanctum::actingAs($secondUser);

        $this->postJson('/api/categories', [
            'name' => 'Work',
            'color' => '#3498db',
        ])->assertCreated();

        $this->assertDatabaseCount('categories', 2);
    }

    public function test_color_must_be_valid_hex_color(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/categories', [
            'name' => 'Work',
            'color' => 'f39c12',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);

        $this->postJson('/api/categories', [
            'name' => 'Work',
            'color' => '#f39c1',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);

        $this->postJson('/api/categories', [
            'name' => 'Work',
            'color' => '#gggggg',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);
    }

    public function test_user_can_show_their_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Ideas',
            'name_normalized' => 'ideas',
            'color' => '#9b59b6',
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Ideas')
            ->assertJsonPath('data.color', '#9b59b6');
    }

    public function test_user_can_update_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Work',
            'name_normalized' => 'work',
            'color' => '#f39c12',
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/categories/{$category->id}", [
            'name' => '  Personal  ',
            'color' => '#3498DB',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Category updated successfully.')
            ->assertJsonPath('data.category.name', 'Personal')
            ->assertJsonPath('data.category.color', '#3498db');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Personal',
            'name_normalized' => 'personal',
            'color' => '#3498db',
        ]);
    }

    public function test_user_cannot_update_category_to_duplicate_name(): void
    {
        $user = User::factory()->create();
        $work = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Work',
            'name_normalized' => 'work',
            'color' => '#f39c12',
        ]);
        Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Personal',
            'name_normalized' => 'personal',
            'color' => '#3498db',
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/categories/{$work->id}", [
            'name' => 'personal',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_cannot_access_another_users_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Private',
            'name_normalized' => 'private',
            'color' => '#3498db',
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/categories/{$category->id}")->assertNotFound();
        $this->patchJson("/api/categories/{$category->id}", ['name' => 'Hacked'])->assertNotFound();
        $this->deleteJson("/api/categories/{$category->id}")->assertNotFound();
    }

    public function test_malformed_category_id_returns_not_found(): void
    {
        $user = User::factory()->create();
        Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Work',
            'name_normalized' => 'work',
            'color' => '#f39c12',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/categories/1abc')->assertNotFound();
        $this->patchJson('/api/categories/1abc', ['name' => 'Personal'])->assertNotFound();
        $this->deleteJson('/api/categories/1abc')->assertNotFound();
    }

    public function test_user_can_delete_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Work',
            'name_normalized' => 'work',
            'color' => '#f39c12',
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Category deleted successfully.');

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }
}
