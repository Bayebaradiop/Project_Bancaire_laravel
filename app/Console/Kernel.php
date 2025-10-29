<?php

namespace App\Console;

use App\Jobs\ActivateBlocageScheduleJob;
use App\Jobs\ActivateDeblocageScheduleJob;
use App\Jobs\BloquerComptesEpargneJob;
use App\Jobs\DebloquerComptesJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Vérifier et activer les blocages programmés toutes les 5 minutes
        $schedule->job(new ActivateBlocageScheduleJob())
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Vérifier et débloquer automatiquement les comptes toutes les 5 minutes
        $schedule->job(new ActivateDeblocageScheduleJob())
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Bloquer automatiquement les comptes épargne dont la date de début de blocage est arrivée
        // et les archiver dans Neon (tous les jours à minuit)
        $schedule->job(new BloquerComptesEpargneJob())
            ->daily()
            ->withoutOverlapping()
            ->runInBackground();

        // Débloquer automatiquement les comptes dont la date de fin de blocage est arrivée
        // et les ramener de Neon vers PostgreSQL (tous les jours à minuit)
        $schedule->job(new DebloquerComptesJob())
            ->daily()
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
