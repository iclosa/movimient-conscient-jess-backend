<?php

namespace Database\Seeders;

use App\Models\Activity;
use Illuminate\Database\Seeder;

/**
 * Càrrega inicial dels textos públics de les 7 activitats (català i castellà) i dels seus colors.
 * Només omple les activitats que encara no tenen contingut: no sobreescriu res del que s'hagi editat al panell.
 */
class ActivityContentSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/activities.json');
        /** @var list<array<string, mixed>> $items */
        $items = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        foreach ($items as $index => $item) {
            $activity = Activity::query()->whereIn('slug', [$item['legacy_slug'], $item['slug']])->first()
                ?? new Activity(['is_active' => true, 'sort_order' => $index + 1]);

            if ($activity->exists && filled($activity->meta_title)) {
                continue;
            }

            $ca = $item['ca'];
            $es = $item['es'];

            $activity->fill([
                'name' => $ca['name'],
                'slug' => $item['slug'],
                'slug_es' => $item['slug_es'],
                'color' => $item['color'],
                'is_family' => $activity->is_family ?: $item['legacy_slug'] === 'yoga-familiar',
            ] + $ca + [
                'name_es' => $es['name'],
                'tagline_es' => $es['tagline'],
                'description_es' => $es['description'],
                'level_es' => $es['level'],
                'h1_es' => $es['h1'],
                'meta_title_es' => $es['meta_title'],
                'meta_description_es' => $es['meta_description'],
                'for_who_es' => $es['for_who'],
                'expect_es' => $es['expect'],
                'benefits_es' => $es['benefits'],
                'faq_es' => $es['faq'],
            ])->save();
        }
    }
}
