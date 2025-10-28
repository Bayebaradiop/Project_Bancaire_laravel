<?php

namespace App\Console;

use App\Jobs\ArchiveComptesBloquesJob;
use App\Jobs\DearchiveComptesBloquesJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Vérifier et archiver les comptes dont le blocage est arrivé (toutes les 5 minutes)
        $schedule->job(new ArchiveComptesBloquesJob())
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Vérifier et débloquer automatiquement les comptes (toutes les 5 minutes)
        $schedule->job(new DearchiveComptesBloquesJob())
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
