<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(BaseSeeder::class);

        // Les dades de mostra només es carreguen en local.
        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
