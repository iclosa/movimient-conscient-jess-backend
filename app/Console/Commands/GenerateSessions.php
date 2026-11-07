<?php

namespace App\Console\Commands;

use App\Services\SessionGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sessions:generate {--weeks=4 : Setmanes per endavant a generar}')]
#[Description('Genera les classes concretes a partir de l\'horari setmanal')]
class GenerateSessions extends Command
{
    public function handle(SessionGenerator $generator): int
    {
        $weeks = max(1, (int) $this->option('weeks'));

        $created = $generator->generate(now()->startOfDay(), now()->addWeeks($weeks)->endOfDay());

        $this->info("Classes creades: {$created}");

        return self::SUCCESS;
    }
}
