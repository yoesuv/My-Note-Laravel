<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class NoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();

        if (! $user) {
            return;
        }

        $categories = $user->categories()
            ->whereIn('name_normalized', ['personal', 'work', 'ideas'])
            ->get()
            ->keyBy('name_normalized');

        $notes = [
            [
                'category' => 'personal',
                'title' => 'Welcome note',
                'content' => 'Use this note to capture quick personal thoughts and reminders.',
            ],
            [
                'category' => 'work',
                'title' => 'Weekly priorities',
                'content' => 'List the most important work tasks to complete this week.',
            ],
            [
                'category' => 'ideas',
                'title' => 'Project ideas',
                'content' => 'Save ideas here before turning them into plans.',
            ],
        ];

        foreach ($notes as $note) {
            $category = $categories->get($note['category']);

            if (! $category) {
                continue;
            }

            $user->notes()->updateOrCreate(
                [
                    'category_id' => $category->id,
                    'title' => $note['title'],
                ],
                [
                    'content' => $note['content'],
                ],
            );
        }
    }
}
