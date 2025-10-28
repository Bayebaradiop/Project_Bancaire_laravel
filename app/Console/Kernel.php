<?php

namespace App\Console;

use App\Jobs\ActivateBlocageScheduleJob;
use App\Jobs\ActivateDeblocageScheduleJob;
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
