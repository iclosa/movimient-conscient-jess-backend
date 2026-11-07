<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Instructor;
use App\Models\Pass;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use App\Models\WellnessItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Datos mínimos para arrancar: administración, catálogo de actividades, bonos y ajustes.
 */
class BaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->admin();
        $this->settings();
        $this->room();
        $this->call(ActivityContentSeeder::class);
        $this->call(BlogPostSeeder::class);
        $this->activities();
        $this->passes();
        $this->wellness();
    }

    private function admin(): void
    {
        $admin = User::query()->firstOrNew(['email' => env('ADMIN_EMAIL', 'jess@movimentconscient.cat')]);
        $admin->forceFill([
            'name' => 'Jèssica Gallardo',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make(env('ADMIN_PASSWORD', 'canvia-aquesta-clau')),
            'email_verified_at' => now(),
        ])->save();
    }

    private function settings(): void
    {
        foreach (Setting::DEFAULTS as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    private function room(): void
    {
        Room::query()->firstOrCreate(['name' => 'Sala principal'], ['capacity' => 12]);
        Instructor::query()->firstOrCreate(
            ['name' => 'Jèssica Gallardo'],
            ['bio' => 'Directora de Moviment Conscient. Yoga, educació postural i meditació guiada.'],
        );
    }

    private function activities(): void
    {
        // Les 7 activitats de la web les crea ActivityContentSeeder (amb els seus textos i colors).
        $catalog = [
            // Surten a l'Instagram però falta confirmar si entren a l'app.
            ['Pilates', '#B66AA8', 'Pendent de confirmar', false],
            ['Circuit 360', '#2BA89F', 'Pendent de confirmar', false],
        ];

        foreach ($catalog as $i => $row) {
            [$name, $color, $tagline, $active] = $row;

            Activity::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'color' => $color,
                    'tagline' => $tagline,
                    'is_active' => $active,
                    'is_family' => $row[4] ?? false,
                    'sort_order' => $i + 8,
                ],
            );
        }
    }

    private function passes(): void
    {
        // Preus d'exemple: cal substituir-los pels reals des del panell (Bons).
        $passes = [
            ['Bo 5 classes', Pass::TYPE_PACK, 5, 60, 8000],
            ['Bo 10 classes', Pass::TYPE_PACK, 10, 90, 11000, true],
            ['Bo 20 classes', Pass::TYPE_PACK, 20, 180, 20000],
            ['Subscripció mensual', Pass::TYPE_SUBSCRIPTION, null, 30, 4500],
        ];

        foreach ($passes as $i => $row) {
            Pass::query()->firstOrCreate(
                ['name' => $row[0]],
                [
                    'type' => $row[1],
                    'classes_count' => $row[2],
                    'validity_days' => $row[3],
                    'price_cents' => $row[4],
                    'is_featured' => $row[5] ?? false,
                    'sort_order' => $i + 1,
                ],
            );
        }
    }

    private function wellness(): void
    {
        $items = [
            ['mindfulness', 'Mindfulness', 'Una pausa de presència per tornar al cos.', 'sparkles', null],
            ['sunrise', 'Sortida del sol', 'Hora de la sortida del sol d\'avui per practicar el Surya Namaskar.', 'sun', null],
            ['breathing', 'Respiració guiada', 'Inspira, atura, expira. Ritme suau per calmar el sistema nerviós.', 'wind', ['inhale' => 4, 'hold' => 4, 'exhale' => 6, 'rounds' => 6]],
            ['meditation_timer', 'Temporitzador de meditació', 'Tria la durada i deixa que la campana t\'avisi.', 'bell', ['default_minutes' => 10, 'options' => [5, 10, 15, 20], 'bell' => true]],
            ['gratitude', 'Diari de gratitud', 'Escriu cada dia una cosa per la qual et sents agraït/ida.', 'heart', null],
        ];

        foreach ($items as $i => [$type, $title, $description, $icon, $config]) {
            WellnessItem::query()->firstOrCreate(
                ['type' => $type, 'title' => $title],
                ['description' => $description, 'icon' => $icon, 'config' => $config, 'sort_order' => $i + 1],
            );
        }
    }
}
