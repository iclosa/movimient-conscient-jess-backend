<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mantiene el calendario siempre generado con 4 semanas de margen.
Schedule::command('sessions:generate --weeks=4')->dailyAt('03:00');
