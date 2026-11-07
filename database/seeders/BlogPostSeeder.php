<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

/**
 * Articles inicials del blog. No destructiu: només s'executa si encara no hi ha cap article.
 */
class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        if (Post::query()->exists()) {
            return;
        }

        /** @var list<array<string, string>> $posts */
        $posts = json_decode((string) file_get_contents(database_path('seeders/data/posts.json')), true);

        foreach ($posts as $post) {
            $date = $post['date'];
            unset($post['date']);
            Post::query()->create($post + ['published_at' => $date.' 09:00:00', 'is_published' => true]);
        }
    }
}
