<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
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

        $categories = [
            ['name' => 'Personal', 'color' => '#3498db'],
            ['name' => 'Work', 'color' => '#2ecc71'],
            ['name' => 'Ideas', 'color' => '#9b59b6'],
        ];

        foreach ($categories as $category) {
            $nameNormalized = Str::lower($category['name']);

            $user->categories()->firstOrCreate(
                ['name_normalized' => $nameNormalized],
                [
                    'name' => $category['name'],
                    'color' => $category['color'],
                ],
            );
        }
    }
}
